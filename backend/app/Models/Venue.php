<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model {
    use SoftDeletes;
    protected $guarded = [];
    public function company() { return $this->belongsTo(Company::class); }
    public function bookings() { return $this->hasMany(VenueBooking::class); }
    public function events() { return $this->hasMany(Event::class); }
}
