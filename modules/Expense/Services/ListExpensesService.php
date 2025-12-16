<?php

declare(strict_types=1);

namespace Modules\Expense\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Expense\Models\Expense;

final class ListExpensesService
{
    public function execute(
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        return Expense::query()
            ->with('deputy')
            ->when($filters['start'] ?? null, fn($q, $v) => $q->where('document_date', '>=', $v))
            ->when($filters['end'] ?? null, fn($q, $v) => $q->where('document_date', '<=', $v))
            ->when($filters['type'] ?? null, fn($q, $v) => $q->byType($v))
            ->when($filters['supplier'] ?? null, fn($q, $v) => $q->bySupplier($v))
            ->orderByDesc('document_date')
            ->paginate($perPage)
            ->appends($filters);
    }
}
