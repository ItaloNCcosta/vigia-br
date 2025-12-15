<?php

declare(strict_types=1);

namespace Modules\Deputy\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Deputy\Models\Deputy;

final class DeputyRankingService
{
    /**
     * Ranking de maiores gastadores.
     *
     * @param array<string, mixed> $filters
     * @param int $limit
     * @return Collection<int, Deputy>
     */
    public function topSpenders(array $filters = [], int $limit = 10): Collection
    {
        return $this->buildRankingQuery($filters)
            ->orderByDesc('total_expenses')
            ->limit($limit)
            ->get();
    }

    /**
     * Ranking de menores gastadores.
     *
     * @param array<string, mixed> $filters
     * @param int $limit
     * @return Collection<int, Deputy>
     */
    public function lowestSpenders(array $filters = [], int $limit = 10): Collection
    {
        return $this->buildRankingQuery($filters)
            ->where('total_expenses', '>', 0)
            ->orderBy('total_expenses')
            ->limit($limit)
            ->get();
    }

    /**
     * Ranking por estado.
     *
     * @param string $stateCode
     * @param int $limit
     * @return Collection<int, Deputy>
     */
    public function topSpendersByState(string $stateCode, int $limit = 10): Collection
    {
        return $this->topSpenders(['state' => $stateCode], $limit);
    }

    /**
     * Ranking por partido.
     *
     * @param string $partyAcronym
     * @param int $limit
     * @return Collection<int, Deputy>
     */
    public function topSpendersByParty(string $partyAcronym, int $limit = 10): Collection
    {
        return $this->topSpenders(['party' => $partyAcronym], $limit);
    }

    /**
     * Média de gastos por estado.
     *
     * @return Collection<int, object>
     */
    public function averageByState(): Collection
    {
        return Deputy::query()
            ->select('state_code')
            ->selectRaw('COUNT(*) as deputies_count')
            ->selectRaw('SUM(total_expenses) as total')
            ->selectRaw('AVG(total_expenses) as average')
            ->selectRaw('MIN(total_expenses) as min')
            ->selectRaw('MAX(total_expenses) as max')
            ->groupBy('state_code')
            ->orderByDesc('average')
            ->get();
    }

    /**
     * Média de gastos por partido.
     *
     * @return Collection<int, object>
     */
    public function averageByParty(): Collection
    {
        return Deputy::query()
            ->select('party_acronym')
            ->selectRaw('COUNT(*) as deputies_count')
            ->selectRaw('SUM(total_expenses) as total')
            ->selectRaw('AVG(total_expenses) as average')
            ->selectRaw('MIN(total_expenses) as min')
            ->selectRaw('MAX(total_expenses) as max')
            ->groupBy('party_acronym')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Estatísticas gerais.
     *
     * @return array<string, mixed>
     */
    public function getGeneralStats(): array
    {
        $stats = Deputy::query()
            ->selectRaw('COUNT(*) as total_deputies')
            ->selectRaw('SUM(total_expenses) as total_expenses')
            ->selectRaw('AVG(total_expenses) as average_expenses')
            ->selectRaw('MIN(total_expenses) as min_expenses')
            ->selectRaw('MAX(total_expenses) as max_expenses')
            ->first();

        return [
            'total_deputies' => (int) $stats->total_deputies,
            'total_expenses' => (float) $stats->total_expenses,
            'average_expenses' => (float) $stats->average_expenses,
            'min_expenses' => (float) $stats->min_expenses,
            'max_expenses' => (float) $stats->max_expenses,
        ];
    }

    /**
     * Ranking com percentil.
     *
     * @param int $limit
     * @return Collection<int, Deputy>
     */
    public function topSpendersWithPercentile(int $limit = 10): Collection
    {
        $maxExpense = Deputy::max('total_expenses') ?: 1;

        return Deputy::query()
            ->with(['party'])
            ->selectRaw('*, (total_expenses / ?) * 100 as percentile', [$maxExpense])
            ->orderByDesc('total_expenses')
            ->limit($limit)
            ->get();
    }

    /**
     * Deputados acima da média.
     *
     * @return Collection<int, Deputy>
     */
    public function aboveAverage(): Collection
    {
        $average = Deputy::avg('total_expenses') ?: 0;

        return Deputy::query()
            ->with(['party'])
            ->where('total_expenses', '>', $average)
            ->orderByDesc('total_expenses')
            ->get();
    }

    /**
     * Deputados abaixo da média.
     *
     * @return Collection<int, Deputy>
     */
    public function belowAverage(): Collection
    {
        $average = Deputy::avg('total_expenses') ?: 0;

        return Deputy::query()
            ->with(['party'])
            ->where('total_expenses', '<', $average)
            ->where('total_expenses', '>', 0)
            ->orderBy('total_expenses')
            ->get();
    }

    /**
     * Comparativo entre dois deputados.
     *
     * @param string $deputyId1
     * @param string $deputyId2
     * @return array<string, mixed>|null
     */
    public function compare(string $deputyId1, string $deputyId2): ?array
    {
        $deputy1 = Deputy::with(['party'])->find($deputyId1);
        $deputy2 = Deputy::with(['party'])->find($deputyId2);

        if (!$deputy1 || !$deputy2) {
            return null;
        }

        $average = Deputy::avg('total_expenses') ?: 0;

        return [
            'deputy1' => [
                'data' => $deputy1,
                'rank' => $this->getRank($deputy1),
                'vs_average' => $deputy1->total_expenses - $average,
            ],
            'deputy2' => [
                'data' => $deputy2,
                'rank' => $this->getRank($deputy2),
                'vs_average' => $deputy2->total_expenses - $average,
            ],
            'difference' => abs($deputy1->total_expenses - $deputy2->total_expenses),
            'higher_spender' => $deputy1->total_expenses > $deputy2->total_expenses ? 'deputy1' : 'deputy2',
        ];
    }

    /**
     * Retorna a posição do deputado no ranking geral.
     */
    public function getRank(Deputy $deputy): int
    {
        return Deputy::where('total_expenses', '>', $deputy->total_expenses)->count() + 1;
    }

    /**
     * Constrói a query base para rankings.
     *
     * @param array<string, mixed> $filters
     * @return Builder
     */
    private function buildRankingQuery(array $filters): Builder
    {
        return Deputy::query()
            ->with(['party'])
            ->when(
                $filters['state'] ?? null,
                fn (Builder $q, string $state) => $q->byState($state)
            )
            ->when(
                $filters['party'] ?? null,
                fn (Builder $q, string $party) => $q->byParty($party)
            )
            ->when(
                $filters['year'] ?? null,
                fn (Builder $q, int $year) => $q->whereHas(
                    'expenses',
                    fn ($eq) => $eq->where('year', $year)
                )
            )
            ->when(
                ($filters['in_exercise'] ?? true) === true,
                fn (Builder $q) => $q->inExercise()
            );
    }
}
