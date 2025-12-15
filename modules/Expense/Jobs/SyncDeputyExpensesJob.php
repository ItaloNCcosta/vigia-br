<?php

declare(strict_types=1);

namespace Modules\Expense\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Expense\Services\ExpenseSyncService;

final class SyncDeputyExpensesJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly int $deputyExternalId,
        public readonly array $filters = []
    ) {
        $this->onQueue('sync_expenses');
    }

    public function handle(ExpenseSyncService $service): void
    {
        $service->syncByExternalId($this->deputyExternalId, $this->filters);
    }
}
