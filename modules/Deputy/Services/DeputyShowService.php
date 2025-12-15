<?php

declare(strict_types=1);

namespace Modules\Deputy\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Deputy\Adapters\CamaraDeputyAdapter;
use Modules\Deputy\Jobs\SyncDeputyDetailsJob;
use Modules\Deputy\Models\Deputy;

final class DeputyShowService
{
    public function __construct(
        private readonly CamaraDeputyAdapter $adapter
    ) {}

    public function find(string $id): ?Deputy
    {
        return Deputy::with(['party', 'legislature'])->find($id);
    }

    public function findOrFail(string $id): Deputy
    {
        return Deputy::with(['party', 'legislature'])->findOrFail($id);
    }

    public function findByExternalId(int $externalId): ?Deputy
    {
        return Deputy::with(['party', 'legislature'])
            ->findByExternalId($externalId);
    }

    public function findByExternalIdOrFail(int $externalId): Deputy
    {
        return Deputy::with(['party', 'legislature'])
            ->findByExternalIdOrFail($externalId);
    }

    public function findAndRefreshIfStale(string $id, int $staleMinutes = 60): ?Deputy
    {
        $deputy = $this->find($id);

        if ($deputy === null) {
            return null;
        }

        if ($deputy->isStale($staleMinutes)) {
            $this->refreshFromApi($deputy);
        }

        return $deputy->fresh(['party', 'legislature']);
    }

    public function findByExternalIdAndRefresh(int $externalId, int $staleMinutes = 60): ?Deputy
    {
        $deputy = $this->findByExternalId($externalId);

        if ($deputy === null) {
            return $this->fetchAndCreateFromApi($externalId);
        }

        if ($deputy->isStale($staleMinutes)) {
            $this->refreshFromApi($deputy);
        }

        return $deputy->fresh(['party', 'legislature']);
    }

    public function refreshFromApi(Deputy $deputy): void
    {
        $data = $this->adapter->find($deputy->external_id);

        if ($data !== null) {
            Deputy::upsertFromApi($deputy->external_id, $data);
        }
    }

    public function refreshFromApiAsync(Deputy $deputy): void
    {
        SyncDeputyDetailsJob::dispatch($deputy->external_id);
    }

    public function fetchAndCreateFromApi(int $externalId): ?Deputy
    {
        $data = $this->adapter->find($externalId);

        if ($data === null) {
            return null;
        }

        return Deputy::upsertFromApi($externalId, $data);
    }

    public function findWithExpenseStats(string $id): ?Deputy
    {
        return Deputy::with(['party', 'legislature'])
            ->withSum('expenses', 'net_value')
            ->withCount('expenses')
            ->find($id);
    }

    public function getExpensesPaginated(
        Deputy $deputy,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        return $deputy->expenses()
            ->when(
                $filters['year'] ?? null,
                fn($q, $year) => $q->where('year', $year)
            )
            ->when(
                $filters['month'] ?? null,
                fn($q, $month) => $q->where('month', $month)
            )
            ->when(
                $filters['expense_type'] ?? null,
                fn($q, $type) => $q->where('expense_type', $type)
            )
            ->when(
                $filters['supplier'] ?? null,
                fn($q, $supplier) => $q->where('supplier_name', 'like', "%{$supplier}%")
            )
            ->orderByDesc('document_date')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate($perPage)
            ->appends($filters);
    }

    public function getAvailableYears(Deputy $deputy): Collection
    {
        return $deputy->expenses()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');
    }

    public function getAvailableMonths(Deputy $deputy, ?int $year = null): Collection
    {
        return $deputy->expenses()
            ->select('month')
            ->when($year, fn($q) => $q->where('year', $year))
            ->distinct()
            ->orderBy('month')
            ->pluck('month');
    }

    public function getAvailableExpenseTypes(Deputy $deputy): Collection
    {
        return $deputy->expenses()
            ->select('expense_type')
            ->distinct()
            ->orderBy('expense_type')
            ->pluck('expense_type');
    }

    public function getExpensesByType(Deputy $deputy): Collection
    {
        return $deputy->expenses()
            ->select('expense_type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(net_value) as total')
            ->groupBy('expense_type')
            ->orderByDesc('total')
            ->get();
    }

    public function getExpensesByMonth(Deputy $deputy, ?int $year = null): Collection
    {
        return $deputy->expenses()
            ->select('year', 'month')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(net_value) as total')
            ->when($year, fn($q) => $q->where('year', $year))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    public function getTopSuppliers(Deputy $deputy, int $limit = 10): Collection
    {
        return $deputy->expenses()
            ->select('supplier_name', 'supplier_document')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(net_value) as total')
            ->whereNotNull('supplier_name')
            ->groupBy('supplier_name', 'supplier_document')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    public function getFinancialSummary(Deputy $deputy): array
    {
        $total = $deputy->expenses()->sum('net_value');

        $monthsCount = $deputy->expenses()
            ->selectRaw('DISTINCT CONCAT(year, "-", LPAD(month, 2, "0")) as period')
            ->count();

        $monthsCount = max($monthsCount, 1);

        $lastExpense = $deputy->expenses()
            ->orderByDesc('document_date')
            ->first();

        return [
            'total' => (float) $total,
            'average_monthly' => $total / $monthsCount,
            'months_count' => $monthsCount,
            'last_expense_date' => $lastExpense?->document_date,
            'expenses_count' => $deputy->expenses()->count(),
        ];
    }
}
