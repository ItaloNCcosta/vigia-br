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

    /**
     * Número de tentativas.
     */
    public int $tries = 3;

    /**
     * Timeout em segundos.
     */
    public int $timeout = 120;

    /**
     * Backoff entre tentativas (segundos).
     *
     * @var array<int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly int $externalId,
        private readonly bool $dispatchExpensesSync = false,
        private readonly ?int $expensesYear = null
    ) {}

    /**
     * Execute the job.
     */
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

        // Atualiza com dados completos
        $deputy = Deputy::upsertFromApi($this->externalId, $data);

        Log::info('Deputy details synced', [
            'external_id' => $this->externalId,
            'name' => $deputy->name,
        ]);

        // Dispara sync de despesas se solicitado
        if ($this->dispatchExpensesSync) {
            $this->dispatchExpensesJob($deputy);
        }
    }

    /**
     * Dispara job de sincronização de despesas.
     */
    private function dispatchExpensesJob(Deputy $deputy): void
    {
        // Se não especificou ano, usa ano atual e anterior
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

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncDeputyDetailsJob failed', [
            'external_id' => $this->externalId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Determine unique ID for this job.
     */
    public function uniqueId(): string
    {
        return 'sync-deputy-details-' . $this->externalId;
    }

    /**
     * Tags para monitoramento.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return ['sync', 'deputy-details', "external:{$this->externalId}"];
    }
}
