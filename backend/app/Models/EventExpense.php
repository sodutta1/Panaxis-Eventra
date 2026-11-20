<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EventExpense extends Model {
    protected $guarded = [];
    public function event() { return $this->belongsTo(Event::class); }
    public function category() { return $this->belongsTo(ExpenseCategory::class, "expense_category_id"); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function approver() { return $this->belongsTo(User::class, "approved_by"); }
}
