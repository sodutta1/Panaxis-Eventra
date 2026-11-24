<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Requests\V1\StoreExpenseRequest;
use App\Http\Requests\V1\UpdateExpenseRequest;
use App\Http\Resources\V1\ExpenseResource;
use App\Models\EventExpense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ScopesToCompany;
    use \App\Http\Controllers\Api\V1\Concerns\QueriesLists;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(EventExpense::query(), $request);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $this->listResponse($query, $request, ExpenseResource::class);
    }

    public function store(StoreExpenseRequest $request)
    {
        $expense = EventExpense::create(array_merge($request->validated(), [
            'company_id' => $this->companyId($request),
            'expense_number' => 'EXP-' . date('Y') . '-' . rand(1000, 9999),
            'created_by' => $request->user()->id,
            'approved_by' => null,
            'status' => 'pending',
        ]));

        return (new ExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function approve(Request $request, int $expense)
    {
        return \Illuminate\Support\Facades\DB::transaction(function() use($request,$expense){
            $model=$this->findForCompany(EventExpense::query()->lockForUpdate(),$request,$expense);
            abort_if((int)$model->created_by===(int)$request->user()->id,422,'Another team member must approve this expense.');
            abort_unless($model->status==='pending',422,'Only pending expenses can be approved.');
            $model->update(['status'=>'approved','approved_by'=>$request->user()->id]);
            return new ExpenseResource($model->fresh());
        });
    }

    public function show(Request $request, int $expense)
    {
        return new ExpenseResource($this->findForCompany(EventExpense::query(), $request, $expense));
    }

    public function update(UpdateExpenseRequest $request, int $expense)
    {
        return \Illuminate\Support\Facades\DB::transaction(function() use($request,$expense){
            $model=$this->findForCompany(EventExpense::query()->lockForUpdate(),$request,$expense);
            abort_unless($model->status==='pending',422,'Approved expenses cannot be edited. Reverse this entry and record a new expense.');
            $model->update($request->validated());return new ExpenseResource($model->fresh());
        });
    }

    public function destroy(Request $request, int $expense)
    {
        $this->findForCompany(EventExpense::query(),$request,$expense);
        return response()->json(['message'=>'Money records cannot be deleted. Reverse the expense with a reason.'], 422);
    }

    public function reverse(Request $request, int $expense)
    {
        $request->validate(['reason'=>'required|string|min:5|max:2000']);
        return \Illuminate\Support\Facades\DB::transaction(function() use($request,$expense){
        $model=$this->findForCompany(EventExpense::query()->lockForUpdate(),$request,$expense);
        abort_if($model->status === 'reversed',422,'This expense is already reversed.');
        $model->update(['status'=>'reversed','reversal_reason'=>$request->input('reason')]);
        \Illuminate\Support\Facades\DB::table('document_downloads')->insert(['company_id'=>$this->companyId($request),'user_id'=>$request->user()->id,'document_type'=>'expense','record_id'=>$model->id,'action'=>'reverse']);
        return new ExpenseResource($model->fresh());
        });
    }

    public function bill(Request $request,int $expense){
        $m=$this->findForCompany(EventExpense::query(),$request,$expense);
        abort_unless($m->bill_data,404,'No bill is attached to this expense.');
        preg_match('/^data:([^;]+);base64,(.+)$/s',$m->bill_data,$parts);
        abort_unless(count($parts)===3,422,'This bill cannot be opened.');
        $name=basename($m->bill_name??'expense-bill');
        return response(base64_decode($parts[2],true),200,['Content-Type'=>$parts[1],'Content-Disposition'=>'attachment; filename="'.str_replace(['"',"\r","\n"],'',$name).'"','X-Content-Type-Options'=>'nosniff']);
    }

}
