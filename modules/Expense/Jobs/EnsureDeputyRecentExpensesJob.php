<?php

declare(strict_types=1);

namespace Modules\Expense\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Modules\Expense\Services\ExpenseSyncService;

final class EnsureDeputyRecentExpensesJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        private readonly string $deputyId
    ) {}

    public function handle(ExpenseSyncService $service): void
    {
        $service->syncRecent($this->deputyId);
    }
}
