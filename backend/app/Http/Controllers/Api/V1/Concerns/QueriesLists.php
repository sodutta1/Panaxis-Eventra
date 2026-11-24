<?php
namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** Shared, tenant-preserving list contract. All identifiers come from the schema, never SQL input. */
trait QueriesLists
{
    protected function listQuery(Builder $query, Request $request): Builder
    {
        $table = $query->getModel()->getTable();
        $columns = Schema::getColumnListing($table);
        $has = fn ($c) => in_array($c, $columns, true);
        $names = array_values(array_filter(['name','title','contact_name','holder_name','business_name','company_name','service_title','item_name'], $has));
        $refs = array_values(array_filter(['id','client_code','vendor_code','event_code','quotation_number','invoice_number','expense_number','ticket_number','receipt_number'], $has));
        $searches = array_values(array_unique(array_merge($names, $refs, array_values(array_filter(['email','phone','holder_email','holder_phone','city','source','category','campaign'], $has)))));
        foreach (['search' => $searches, 'name' => $names, 'reference' => $refs] as $param => $fields) {
            if ($request->filled($param) && $fields) {
                $term = '%'.str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], mb_substr((string)$request->input($param),0,200)).'%';
                $query->where(function ($q) use ($fields,$term,$param,$table) {
                    foreach ($fields as $field) $q->orWhere($table.'.'.$field, 'like', $term);
                    if (in_array($table,['quotations','invoices']) && in_array($param,['search','name'])) {
                        $q->orWhereHas('client', fn ($c) => $c->where('name','like',$term)->orWhere('email','like',$term)->orWhere('phone','like',$term));
                    }
                });
            }
        }
        $status = $has('status') ? 'status' : ($has('rsvp_status') ? 'rsvp_status' : ($has('ticket_status') ? 'ticket_status' : null));
        if ($status && $request->filled('status')) $query->where($table.'.'.$status,$request->string('status')->toString());
        foreach (['event_id','vendor_id','expense_category_id','vendor_category_id','client_id','payment_method','city','type','source','campaign'] as $field) {
            if ($has($field) && $request->filled($field)) $query->where($table.'.'.$field,$request->input($field));
        }
        $owner = collect(['assigned_to','manager_id','created_by'])->first($has);
        if ($request->filled('owner') && $owner) $query->where($table.'.'.$owner,$request->input('owner'));
        $date = collect(['incurred_date','issue_date','invoice_date','event_date','start_date','created_at'])->first($has);
        foreach (['from'=>'>=','to'=>'<='] as $param=>$op) if ($date && $request->filled($param)) {
            $request->validate([$param=>'date_format:Y-m-d']);
            $query->whereDate($table.'.'.$date,$op,$request->input($param));
        }
        $sort = (string)$request->input('sort','id');
        // Deny private fields and non-column expressions even if a caller bypasses the UI.
        $safe = array_diff($columns,['password','remember_token','qr_token','receipt_path']);
        if (!in_array($sort,$safe,true)) $sort = 'id';
        return $query->reorder()->orderBy($table.'.'.$sort,$request->input('direction') === 'asc' ? 'asc' : 'desc')->orderBy($table.'.id');
    }

    protected function listResponse(Builder $query, Request $request, ?string $resource = null)
    {
        $query = $this->listQuery($query,$request);
        if (in_array($request->input('format'),['csv','xlsx'],true)) return \App\Support\ListExport::response($query,$request);
        $size = (int)$request->input('per_page',25);
        if (!in_array($size,[10,20,25,50,100],true)) $size = 25;
        $page = $query->paginate($size)->withQueryString();
        return $resource ? $resource::collection($page) : response()->json(['data'=>$page->items(),'meta'=>['total'=>$page->total(),'from'=>$page->firstItem(),'to'=>$page->lastItem(),'current_page'=>$page->currentPage(),'last_page'=>$page->lastPage(),'per_page'=>$size]]);
    }
}
