<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

/** Validate ownership as well as existence before writing tenant relationships. */
abstract class TenantRequest extends FormRequest
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $rules = $this->rules();
            $references = [
                'client_id' => 'clients', 'event_id' => 'events', 'venue_id' => 'venues',
                'manager_id' => 'users', 'assigned_to' => 'users', 'vendor_id' => 'vendors',
                'event_category_id' => 'event_categories', 'vendor_category_id' => 'vendor_categories',
                'expense_category_id' => 'expense_categories', 'guest_group_id' => 'guest_groups',
                'ticket_type_id' => 'ticket_types',
            ];
            foreach ($references as $field => $table) {
                if (! array_key_exists($field, $rules) || ! $this->filled($field) || $validator->errors()->has($field)) {
                    continue;
                }
                if (! DB::table($table)->where('id', $this->input($field))->where('company_id', $this->user()->company_id)->exists()) {
                    $validator->errors()->add($field, 'The selected '.$field.' is invalid.');
                }
            }
            $eventId = $this->input('event_id') ?? $this->route('event');
            if (! $eventId && $this->route('guest')) {
                $eventId = DB::table('event_guests')->where('id', $this->route('guest'))->where('company_id', $this->user()->company_id)->value('event_id');
            }
            if ($eventId && ! $validator->errors()->has('event_id') && isset($rules['client_id']) && $this->filled('client_id') && ! $validator->errors()->has('client_id')
                && in_array(static::class, [StoreInvoiceRequest::class, StoreQuotationRequest::class], true)) {
                $clientId = DB::table('events')->where('id', $eventId)->where('company_id', $this->user()->company_id)->value('client_id');
                if ($clientId && (int) $clientId !== (int) $this->input('client_id')) {
                    $validator->errors()->add('client_id', 'The selected client does not own this event.');
                }
            }
            if ($this->route('guest') && $this->has('event_id') && ! $this->has('guest_group_id') && ! $validator->errors()->has('event_id')) {
                $groupId = DB::table('event_guests')->where('id', $this->route('guest'))->where('company_id', $this->user()->company_id)->value('guest_group_id');
                if ($groupId && ! DB::table('guest_groups')->where('id', $groupId)->where('event_id', $eventId)->exists()) {
                    $validator->errors()->add('guest_group_id', 'Clear or replace the group when moving a guest to another event.');
                }
            }
            foreach (['guest_group_id' => 'guest_groups', 'ticket_type_id' => 'ticket_types', 'event_day_id' => 'event_days'] as $field => $table) {
                if (array_key_exists($field, $rules) && $this->filled($field) && $eventId && ! $validator->errors()->has('event_id') && ! $validator->errors()->has($field)
                    && ! DB::table($table)->where('id', $this->input($field))->where('event_id', $eventId)->exists()) {
                    $validator->errors()->add($field, 'The selected record does not belong to this event.');
                }
            }
        });
    }
}
