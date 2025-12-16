<?php

declare(strict_types=1);

namespace Modules\Expense\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Deputy\Models\Deputy;

final class SyncAllExpensesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 1800;

    public function __construct(
        private readonly ?int $year = null
    ) {}

    public function handle(): void
    {
        $year = $this->year ?? (int) date('Y');

        Log::info('SyncAllExpensesJob: Iniciando', ['year' => $year]);

        $deputies = Deputy::all();

        foreach ($deputies as $deputy) {
            SyncDeputyExpensesJob::dispatch($deputy->id, $year);
        }

        Log::info('SyncAllExpensesJob: Jobs disparados', ['total' => $deputies->count()]);
    }
}
