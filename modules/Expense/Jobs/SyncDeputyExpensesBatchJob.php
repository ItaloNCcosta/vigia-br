<?php

declare(strict_types=1);

namespace Modules\Expense\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Deputy\Models\Deputy;
use Modules\Expense\Services\ExpenseSyncService;

final class SyncDeputyExpensesBatchJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(ExpenseSyncService $service): void
    {
        $years = $service->getYearsToSync();
        $jobs = [];

        Deputy::select('id')->chunk(50, function ($deputies) use (&$jobs, $years) {
            foreach ($deputies as $deputy) {
                foreach ($years as $year) {
                    $jobs[] = new SyncDeputyExpensesJob($deputy->id, $year);
                }
            }
        });

        Bus::batch($jobs)
            ->name('sync-deputy-expenses')
            ->onQueue('heavy')
            ->dispatch();
    }
}
