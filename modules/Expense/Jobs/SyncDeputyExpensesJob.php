<?php

declare(strict_types=1);

namespace Modules\Expense\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Expense\Services\ExpenseSyncService;

final class SyncDeputyExpensesJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        private readonly string $deputyId,
        private readonly int $year
    ) {}

    public function handle(ExpenseSyncService $service): void
    {
        $service->syncByYear($this->deputyId, $this->year);
    }
}
