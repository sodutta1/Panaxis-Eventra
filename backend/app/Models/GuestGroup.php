<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class GuestGroup extends Model {
    protected $guarded = [];
    public function guests() { return $this->hasMany(EventGuest::class); }
}
