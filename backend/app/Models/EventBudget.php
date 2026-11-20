<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EventBudget extends Model {
    protected $guarded = [];
    public function event() { return $this->belongsTo(Event::class); }
    public function items() { return $this->hasMany(EventBudgetItem::class); }
}
