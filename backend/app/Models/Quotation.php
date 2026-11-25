<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model {
    use SoftDeletes;
    protected $guarded = [];
    public function client() { return $this->belongsTo(Client::class); }
    public function event() { return $this->belongsTo(Event::class); }
    public function proposal() { return $this->belongsTo(Proposal::class); }
    public function items() { return $this->hasMany(QuotationItem::class); }
}
