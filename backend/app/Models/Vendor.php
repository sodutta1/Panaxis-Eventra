<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model {
    protected $guarded = [];
    public function category() { return $this->belongsTo(VendorCategory::class, "vendor_category_id"); }
    public function assignments() { return $this->hasMany(VendorAssignment::class); }
    public function expenses() { return $this->hasMany(EventExpense::class); }
}
