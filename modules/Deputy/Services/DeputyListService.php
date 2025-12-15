<?php

declare(strict_types=1);

namespace Modules\Deputy\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Deputy\Models\Deputy;

final class DeputyListService
{
    /**
     * Lista deputados com filtros e paginação.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
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

    /**
     * Lista deputados por estado.
     *
     * @param string $stateCode
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listByState(string $stateCode, int $perPage = 20): LengthAwarePaginator
    {
        return $this->list(['state' => $stateCode], $perPage);
    }

    /**
     * Lista deputados por partido.
     *
     * @param string $partyAcronym
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listByParty(string $partyAcronym, int $perPage = 20): LengthAwarePaginator
    {
        return $this->list(['party' => $partyAcronym], $perPage);
    }

    /**
     * Busca deputados por nome.
     *
     * @param string $name
     * @param int $limit
     * @return Collection<int, Deputy>
     */
    public function search(string $name, int $limit = 10): Collection
    {
        return Deputy::query()
            ->byName($name)
            ->inExercise()
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Retorna todos os estados com deputados.
     *
     * @return Collection<int, object>
     */
    public function getStatesWithDeputies(): Collection
    {
        return Deputy::query()
            ->select('state_code')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('state_code')
            ->orderBy('state_code')
            ->get();
    }

    /**
     * Retorna todos os partidos com deputados.
     *
     * @return Collection<int, object>
     */
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

    /**
     * Conta total de deputados.
     *
     * @param array<string, mixed> $filters
     * @return int
     */
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
