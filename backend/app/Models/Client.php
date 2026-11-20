<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Client extends Model {
    protected $guarded = [];
    public function company() { return $this->belongsTo(Company::class); }
    public function events() { return $this->hasMany(Event::class); }
    public function proposals() { return $this->hasMany(Proposal::class); }
    public function quotations() { return $this->hasMany(Quotation::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
}
