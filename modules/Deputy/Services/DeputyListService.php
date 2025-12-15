<?php

declare(strict_types=1);

namespace Modules\Deputy\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Deputy\Models\Deputy;

final class DeputyListService
{
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Deputy::query()
            ->with(['party'])
            ->when(
                $filters['name'] ?? null,
                fn (Builder $q, string $name) => $q->byName($name)
            )
            ->when(
                $filters['state'] ?? null,
                fn (Builder $q, string $state) => $q->byState($state)
            )
            ->when(
                $filters['party'] ?? null,
                fn (Builder $q, string $party) => $q->byParty($party)
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $q, string $status) => $q->where('status', $status)
            )
            ->when(
                ($filters['in_exercise'] ?? false) === true,
                fn (Builder $q) => $q->inExercise()
            )
            ->orderBy($filters['order_by'] ?? 'name', $filters['order'] ?? 'asc')
            ->paginate($perPage)
            ->appends($filters);
    }

    public function listByState(string $stateCode, int $perPage = 20): LengthAwarePaginator
    {
        return $this->list(['state' => $stateCode], $perPage);
    }

    public function listByParty(string $partyAcronym, int $perPage = 20): LengthAwarePaginator
    {
        return $this->list(['party' => $partyAcronym], $perPage);
    }

    public function search(string $name, int $limit = 10): Collection
    {
        return Deputy::query()
            ->byName($name)
            ->inExercise()
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function getStatesWithDeputies(): Collection
    {
        return Deputy::query()
            ->select('state_code')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('state_code')
            ->orderBy('state_code')
            ->get();
    }

    public function getPartiesWithDeputies(): Collection
    {
        return Deputy::query()
            ->select('party_acronym')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total_expenses) as total_expenses')
            ->groupBy('party_acronym')
            ->orderByDesc('count')
            ->get();
    }

    public function count(array $filters = []): int
    {
        return Deputy::query()
            ->when(
                $filters['state'] ?? null,
                fn (Builder $q, string $state) => $q->byState($state)
            )
            ->when(
                $filters['party'] ?? null,
                fn (Builder $q, string $party) => $q->byParty($party)
            )
            ->when(
                ($filters['in_exercise'] ?? false) === true,
                fn (Builder $q) => $q->inExercise()
            )
            ->count();
    }
}
