<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;

/** A user's own notification feed. Every query is pinned to the caller's user and company. */
class NotificationController extends Controller
{
    private function mine(Request $request)
    {
        $u = $request->user();
        return AppNotification::where('user_id', $u->id)->where('company_id', $u->company_id);
    }

    private function row(AppNotification $n): array
    {
        return ['id' => $n->id, 'type' => $n->type, 'title' => $n->title, 'body' => $n->body, 'link' => $n->link,
            'read' => $n->read_at !== null, 'created_at' => $n->created_at?->toIso8601String()];
    }

    public function index(Request $request)
    {
        $per = max(1, min(50, (int) $request->input('per_page', 20)));
        $q = $this->mine($request)->orderByDesc('id');
        if ($request->input('filter') === 'unread') {
            $q->whereNull('read_at');
        }
        $page = $q->paginate($per);
        return response()->json([
            'data' => $page->getCollection()->map(fn ($n) => $this->row($n))->values(),
            'unread_count' => $this->mine($request)->whereNull('read_at')->count(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function unreadCount(Request $request)
    {
        return response()->json(['unread_count' => $this->mine($request)->whereNull('read_at')->count()]);
    }

    public function read(Request $request, int $notification)
    {
        $n = $this->mine($request)->whereKey($notification)->firstOrFail();
        if (! $n->read_at) {
            $n->update(['read_at' => now()]);
        }
        return response()->json(['data' => $this->row($n->fresh()), 'unread_count' => $this->mine($request)->whereNull('read_at')->count()]);
    }

    public function readAll(Request $request)
    {
        $this->mine($request)->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['unread_count' => 0]);
    }
}
