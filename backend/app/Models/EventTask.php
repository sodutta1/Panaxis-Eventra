<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventTask extends Model
{
    public const PHASES = ['pre_event', 'on_event', 'post_event'];

    protected $guarded = [];

    protected $casts = ['due_date' => 'date', 'reminder_sent_at' => 'datetime'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function dependency()
    {
        return $this->belongsTo(EventTask::class, 'depends_on');
    }

    public function dependents()
    {
        return $this->hasMany(EventTask::class, 'depends_on');
    }

    /** A task is blocked until its dependency completes. */
    public function isBlocked(): bool
    {
        $dep = $this->dependency;

        return $dep !== null && $dep->status !== 'completed';
    }
}
