<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingOrder extends Model
{
    protected $guarded = [];

    protected $casts = ['expires_at' => 'datetime'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
