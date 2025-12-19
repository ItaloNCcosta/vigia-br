<?php

declare(strict_types=1);

namespace Modules\Expense\Services;

use Modules\Deputy\Models\Deputy;
use Modules\Expense\Adapters\CamaraExpenseAdapter;
use Modules\Expense\Models\Expense;

final class ExpenseSyncService
{
    public function __construct(
        private readonly CamaraExpenseAdapter $adapter
    ) {}

    public function syncByYear(int $deputyId, int $year): void
    {
        $deputy = Deputy::select('id', 'external_id')->find($deputyId);

        if (!$deputy) {
            return;
        }

        $payload = [];

        foreach ($this->adapter->listByDeputy($deputy->external_id, ['ano' => $year]) as $item) {
            $payload[] = $this->adapter->mapToModel($item, (string) $deputy->id);
        }

        if ($payload === []) {
            return;
        }

        Expense::upsert(
            $payload,
            ['deputy_id', 'external_id'],
            array_keys($payload[0])
        );
    }

    public function syncRecent(int $deputyId): void
    {
        $this->syncByYear($deputyId, (int) date('Y'));
    }

    public function getYearsToSync(): array
    {
        $currentYear = (int) date('Y');

        return range(2019, $currentYear);
    }
}
