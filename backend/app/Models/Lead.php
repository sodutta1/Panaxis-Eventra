<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model {
    use SoftDeletes;
    protected $guarded = [];
    public function company() { return $this->belongsTo(Company::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function assignedUser() { return $this->belongsTo(User::class, "assigned_to"); }
    public function activities() { return $this->hasMany(LeadActivity::class); }
}
