<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Proposal extends Model {
    protected $guarded = [];
    public function client() { return $this->belongsTo(Client::class); }
    public function event() { return $this->belongsTo(Event::class); }
    public function quotations() { return $this->hasMany(Quotation::class); }
}
