<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class InventoryAllocation extends Model {
    protected $guarded = [];
    public function event() { return $this->belongsTo(Event::class); }
    public function item() { return $this->belongsTo(InventoryItem::class, "inventory_item_id"); }
}
