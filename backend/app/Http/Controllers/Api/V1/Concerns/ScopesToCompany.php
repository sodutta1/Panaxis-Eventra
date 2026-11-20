<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tenant isolation helper. Every tenant-owned table carries company_id;
 * API queries are always restricted to the authenticated user's company,
 * and cross-tenant model access resolves as 404 (not 403) so the
 * existence of another tenant's records is never leaked.
 */
trait ScopesToCompany
{
    protected function companyId(Request $request): int
    {
        return (int) ($request->user()->company_id ?? 1);
    }

    protected function scopeCompany(Builder $query, Request $request): Builder
    {
        return $query->where('company_id', $this->companyId($request));
    }

    protected function findForCompany(Builder $query, Request $request, int $id): Model
    {
        $model = $this->scopeCompany($query, $request)->find($id);

        if (!$model) {
            throw new NotFoundHttpException('Resource not found.');
        }

        return $model;
    }
}
