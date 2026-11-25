<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

/**
 * In-app notification fan-out. Recipients are always inside one company and
 * limited to roles that may open the linked screen, so a notification never
 * reveals more than the recipient could see on desktop.
 */
class Notifier
{
    public const FINANCE = ['super_admin', 'company_admin', 'admin', 'accountant'];
    public const SALES = ['super_admin', 'company_admin', 'admin', 'event_manager', 'sales_executive'];

    /** Notify one user. Never notifies the person who caused the event. */
    public static function user(?int $userId, int $companyId, string $type, string $title, ?string $body = null, ?string $link = null, ?int $actorId = null): void
    {
        if (! $userId || $userId === $actorId) {
            return;
        }
        $ok = User::whereKey($userId)->where('company_id', $companyId)->where('status', 'active')->exists();
        if (! $ok) {
            return;
        }
        AppNotification::create(['company_id' => $companyId, 'user_id' => $userId, 'type' => $type,
            'title' => mb_substr($title, 0, 160), 'body' => $body ? mb_substr($body, 0, 400) : null, 'link' => $link]);
    }

    /** Notify every active user in the company holding one of the roles. */
    public static function roles(array $roles, int $companyId, string $type, string $title, ?string $body = null, ?string $link = null, ?int $actorId = null): void
    {
        User::where('company_id', $companyId)->where('status', 'active')->whereIn('role', $roles)
            ->when($actorId, fn ($q) => $q->where('id', '!=', $actorId))
            ->pluck('id')
            ->each(fn ($id) => self::user($id, $companyId, $type, $title, $body, $link, $actorId));
    }
}
