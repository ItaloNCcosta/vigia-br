<?php

declare(strict_types=1);

namespace Modules\Deputy\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Deputy\Adapters\CamaraDeputyAdapter;
use Modules\Deputy\Models\Deputy;
use Modules\Expense\Jobs\SyncDeputyExpensesJob;

final class SyncDeputyDetailsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Batchable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly int $externalId,
        private readonly bool $dispatchExpensesSync = false,
        private readonly ?int $expensesYear = null
    ) {}

    public function handle(CamaraDeputyAdapter $adapter): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        Log::info('SyncDeputyDetailsJob started', [
            'external_id' => $this->externalId,
        ]);

        $data = $adapter->find($this->externalId);

        if ($data === null) {
            Log::warning('Deputy not found in API', [
                'external_id' => $this->externalId,
            ]);
            return;
        }

        $deputy = Deputy::upsertFromApi($this->externalId, $data);

        Log::info('Deputy details synced', [
            'external_id' => $this->externalId,
            'name' => $deputy->name,
        ]);

        if ($this->dispatchExpensesSync) {
            $this->dispatchExpensesJob($deputy);
        }
    }

    private function dispatchExpensesJob(Deputy $deputy): void
    {
        if ($this->expensesYear === null) {
            $currentYear = (int) date('Y');

            SyncDeputyExpensesJob::dispatch($deputy->id, $currentYear);
            SyncDeputyExpensesJob::dispatch($deputy->id, $currentYear - 1);
        } else {
            SyncDeputyExpensesJob::dispatch($deputy->id, $this->expensesYear);
        }

        Log::info('Expenses sync dispatched for deputy', [
            'deputy_id' => $deputy->id,
            'external_id' => $this->externalId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncDeputyDetailsJob failed', [
            'external_id' => $this->externalId,
            'error' => $exception->getMessage(),
        ]);
    }

    public function uniqueId(): string
    {
        return 'sync-deputy-details-' . $this->externalId;
    }

    public function tags(): array
    {
        return ['sync', 'deputy-details', "external:{$this->externalId}"];
    }
}
