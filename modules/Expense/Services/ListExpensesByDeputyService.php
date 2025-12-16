<?php

declare(strict_types=1);

namespace Modules\Expense\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Deputy\Models\Deputy;

final class ListExpensesByDeputyService
{
    public function execute(
        Deputy $deputy,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        return $deputy->expenses()
            ->when($filters['year'] ?? null, fn($q, $v) => $q->byYear((int) $v))
            ->when($filters['month'] ?? null, fn($q, $v) => $q->byMonth((int) $v))
            ->orderByDesc('document_date')
            ->paginate($perPage)
            ->appends($filters);
    }
}
