<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class VendorAssignment extends Model {
    // Forward lifecycle: assigned -> in_progress -> completed -> paid,
    // cancellable from any non-terminal state. Paid and cancelled are terminal.
    public const STATUS_RANK = [
        'assigned' => 0,
        'in_progress' => 1,
        'completed' => 2,
        'paid' => 3,
    ];

    public const TERMINAL_STATUSES = ['paid', 'cancelled'];

    public function canTransitionTo(string $status): bool
    {
        if ($status === $this->status) {
            return true;
        }

        if (in_array($this->status, self::TERMINAL_STATUSES, true)) {
            return false;
        }

        if ($status === 'cancelled') {
            return true;
        }

        $from = self::STATUS_RANK[$this->status] ?? null;
        $to = self::STATUS_RANK[$status] ?? null;

        return $from !== null && $to !== null && $to > $from;
    }

    protected $guarded = [];
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function event() { return $this->belongsTo(Event::class); }
}
