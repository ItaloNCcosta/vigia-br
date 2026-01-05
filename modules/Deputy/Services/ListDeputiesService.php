<?php

declare(strict_types=1);

namespace Modules\Deputy\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Deputy\Models\Deputy;

final class ListDeputiesService
{
    public function execute(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $year = $filters['year'] ?? now()->year;
        return Deputy::query()
            ->select([
                'id',
                'name',
                'photo_url',
                'party_acronym',
                'state_code',
                'last_synced_at',
            ])
            ->withSum(
                [
                    'expenses as total_expenses' => fn($q) =>
                    $q->where('year', $year)
                ],
                'net_value'
            )
            ->when($filters['name'] ?? null, fn($q, $v) => $q->byName($v))
            ->when($filters['state'] ?? null, fn($q, $v) => $q->byState($v))
            ->when($filters['party'] ?? null, fn($q, $v) => $q->byParty($v))
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($filters);
    }
}
