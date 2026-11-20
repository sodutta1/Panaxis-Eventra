<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class VenueBooking extends Model {
    protected $guarded = [];
    public function venue() { return $this->belongsTo(Venue::class); }
    public function event() { return $this->belongsTo(Event::class); }
}
