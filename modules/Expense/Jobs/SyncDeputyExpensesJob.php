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

final class SyncDeputyExpensesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(
        private readonly string $deputyId,
        private readonly int $year
    ) {}

    public function handle(ExpenseSyncService $service): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $service->syncByYear($this->deputyId, $this->year);
    }
}
