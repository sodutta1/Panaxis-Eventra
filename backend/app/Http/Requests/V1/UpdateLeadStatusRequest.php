<?php

namespace App\Http\Requests\V1;

class UpdateLeadStatusRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => 'required|string|in:new,contacted,qualified,proposal_sent,negotiating,converted,lost|max:255'];
    }
}
