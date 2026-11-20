<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EventBudgetItem extends Model {
    protected $guarded = [];
    public function budget() { return $this->belongsTo(EventBudget::class, "event_budget_id"); }
}
