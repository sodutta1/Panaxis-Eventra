<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model {
    protected $guarded = [];
    public function client() { return $this->belongsTo(Client::class); }
    public function event() { return $this->belongsTo(Event::class); }
    public function proposal() { return $this->belongsTo(Proposal::class); }
    public function items() { return $this->hasMany(QuotationItem::class); }
}
