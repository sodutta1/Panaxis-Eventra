<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Undo for deletes. Deleted records are kept (soft delete) and can be brought
 * back by admins. POST /api/v1/restore/{type}/{id}
 */
class RestoreController extends Controller
{
    use ScopesToCompany;

    public const TYPES = [
        'leads' => \App\Models\Lead::class,
        'clients' => \App\Models\Client::class,
        'venues' => \App\Models\Venue::class,
        'vendors' => \App\Models\Vendor::class,
        'guests' => \App\Models\EventGuest::class,
        'events' => \App\Models\Event::class,
        'quotations' => \App\Models\Quotation::class,
    ];

    public function restore(Request $request, string $type, string $id)
    {
        $class = self::TYPES[$type] ?? null;
        if (! $class || ! ctype_digit($id) || strlen($id) > 18) {
            throw new NotFoundHttpException('Resource not found.');
        }
        $model = $class::onlyTrashed()->where('company_id', $this->companyId($request))->find((int) $id);
        if (! $model) {
            throw new NotFoundHttpException('Nothing to restore.');
        }
        $model->restore();

        return response()->json(['message' => 'Restored.', 'data' => ['type' => $type, 'id' => $model->id]]);
    }
}
