<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Company extends Model {
    protected $guarded = [];
    public function branches() { return $this->hasMany(Branch::class); }
    public function users() { return $this->hasMany(User::class); }
    public function clients() { return $this->hasMany(Client::class); }
    public function events() { return $this->hasMany(Event::class); }
    public function venues() { return $this->hasMany(Venue::class); }
    public function vendors() { return $this->hasMany(Vendor::class); }
}
