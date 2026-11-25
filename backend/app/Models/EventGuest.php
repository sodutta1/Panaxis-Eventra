<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventGuest extends Model {
    use SoftDeletes;
    protected $guarded = [];
    public function event() { return $this->belongsTo(Event::class); }
    public function group() { return $this->belongsTo(GuestGroup::class, "guest_group_id"); }
}
