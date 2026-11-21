<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    protected $guarded = [];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path
            ? Storage::disk('public')->url($this->logo_path)
            : null;
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function venues()
    {
        return $this->hasMany(Venue::class);
    }

    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }
}
