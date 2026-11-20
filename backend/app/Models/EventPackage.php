<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EventPackage extends Model {
    protected $guarded = [];
    public function category() { return $this->belongsTo(EventCategory::class, "event_category_id"); }
}
