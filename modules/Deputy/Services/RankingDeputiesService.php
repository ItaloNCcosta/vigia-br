<?php

declare(strict_types=1);

namespace Modules\Deputy\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Deputy\Models\Deputy;

final class RankingDeputiesService
{
    public function execute(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Deputy::query()
            ->withSum('expenses', 'net_value')
            ->when($filters['name'] ?? null, fn($q, $v) => $q->byName($v))
            ->when($filters['state'] ?? null, fn($q, $v) => $q->byState($v))
            ->when($filters['party'] ?? null, fn($q, $v) => $q->byParty($v))
            ->orderByDesc('expenses_sum_net_value')
            ->paginate($perPage)
            ->appends($filters);
    }
}
