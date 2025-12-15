<?php

declare(strict_types=1);

namespace Modules\Expense\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Modules\Deputy\Models\Deputy;

final class SyncAllExpensesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly array $filters = []
    ) {
        $this->onQueue('sync');
    }

    public function handle(): void
    {
        Deputy::query()
            ->chunkById(100, function ($deputies) {
                $jobs = $deputies->map(fn($d) => 
                    new SyncDeputyExpensesJob($d->external_id, $this->filters)
                );

                Bus::batch($jobs->all())
                    ->name('Sync All Expenses')
                    ->onQueue('sync_expenses')
                    ->dispatch();
            });
    }
}