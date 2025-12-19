<?php

declare(strict_types=1);

namespace Modules\Expense\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Expense\Services\ExpenseSyncService;

final class EnsureDeputyRecentExpensesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        private readonly string $deputyId
    ) {
        Log::info('EnsureDeputyRecentExpensesJob criado', ['deputy_id' => $deputyId]);
    }

    public function handle(ExpenseSyncService $service): void
    {
        Log::info('EnsureDeputyRecentExpensesJob iniciado', ['deputy_id' => $this->deputyId]);

        $service->syncRecent($this->deputyId);

        Log::info('EnsureDeputyRecentExpensesJob finalizado', ['deputy_id' => $this->deputyId]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EnsureDeputyRecentExpensesJob falhou', [
            'deputy_id' => $this->deputyId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
