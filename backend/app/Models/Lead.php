<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model {
    protected $guarded = [];
    public function company() { return $this->belongsTo(Company::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function assignedUser() { return $this->belongsTo(User::class, "assigned_to"); }
    public function activities() { return $this->hasMany(LeadActivity::class); }
}
