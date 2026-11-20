<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class VendorAssignment extends Model {
    protected $guarded = [];
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function event() { return $this->belongsTo(Event::class); }
}
