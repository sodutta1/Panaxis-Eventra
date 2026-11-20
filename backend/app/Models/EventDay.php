<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EventDay extends Model {
    protected $guarded = [];
    public function event() { return $this->belongsTo(Event::class); }
    public function schedules() { return $this->hasMany(EventSchedule::class); }
}
