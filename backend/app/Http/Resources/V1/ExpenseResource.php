<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'created_by'=>$this->created_by, 'gst_amount'=>$this->gst_amount, 'campaign'=>$this->campaign, 'category_name'=>$this->category_name, 'bill_name'=>$this->bill_name, 'has_bill'=>(bool)$this->bill_data, 'reversal_reason'=>$this->reversal_reason,
            'id' => $this->id,
            'company_id' => $this->company_id,
            'expense_number' => $this->expense_number,
            'event_id' => $this->event_id,
            'expense_category_id' => $this->expense_category_id,
            'vendor_id' => $this->vendor_id,
            'title' => $this->title,
            'amount' => $this->amount,
            'incurred_date' => $this->incurred_date,
            'payment_method' => $this->payment_method,
            'receipt_number' => $this->receipt_number,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
