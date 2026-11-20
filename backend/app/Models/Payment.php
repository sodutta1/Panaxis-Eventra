<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model {
    protected $guarded = [];
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function event() { return $this->belongsTo(Event::class); }
}
