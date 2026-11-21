<?php

namespace App\Http\Controllers\API\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait PaginatesApiResults
{
    /**
     * Paginate a query using the shared API page / per_page rules.
     */
    protected function paginateQuery(Builder $query, Request $request): LengthAwarePaginator
    {
        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $perPage = $validated['per_page'] ?? 50;

        return $query->paginate($perPage)->appends($request->query());
    }

    /**
     * Wrap a paginator in the shared `{ data, meta }` envelope.
     *
     * @return array{data: mixed, meta: array{current_page: int, last_page: int, per_page: int, total: int}}
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
