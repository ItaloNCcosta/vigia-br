<?php

declare(strict_types=1);

namespace Modules\Deputy\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Modules\Deputy\Adapters\CamaraDeputyAdapter;
use Modules\Deputy\Models\Deputy;
use Modules\Shared\Http\CamaraApiClient;

final class SyncAllDeputiesJob implements ShouldQueue
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
    public int $timeout = 300;

    /**
     * Se deve sincronizar detalhes completos.
     */
    public function __construct(
        private readonly bool $syncDetails = false,
        private readonly bool $dispatchExpensesSync = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CamaraDeputyAdapter $adapter): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        Log::info('SyncAllDeputiesJob started', [
            'sync_details' => $this->syncDetails,
            'dispatch_expenses' => $this->dispatchExpensesSync,
        ]);

        $created = 0;
        $updated = 0;
        $deputyJobs = [];

        foreach ($adapter->listCurrentDeputies() as $data) {
            $externalId = (int) $data['id'];
            $exists = Deputy::existsByExternalId($externalId);

            // Cria/atualiza com dados básicos
            Deputy::upsertByExternalId($externalId, [
                'name' => $data['nome'] ?? '',
                'electoral_name' => $data['nome'] ?? null,
                'state_code' => $data['siglaUf'] ?? '',
                'party_acronym' => $data['siglaPartido'] ?? '',
                'email' => $data['email'] ?? null,
                'photo_url' => $data['urlFoto'] ?? null,
                'uri' => $data['uri'] ?? null,
                'last_synced_at' => now(),
            ]);

            $exists ? $updated++ : $created++;

            // Agenda jobs de detalhes/despesas
            if ($this->syncDetails || $this->dispatchExpensesSync) {
                $deputyJobs[] = new SyncDeputyDetailsJob(
                    $externalId,
                    $this->dispatchExpensesSync
                );
            }
        }

        // Dispatch batch de jobs de detalhes
        if (!empty($deputyJobs)) {
            Bus::batch($deputyJobs)
                ->name('sync-deputies-details')
                ->allowFailures()
                ->dispatch();
        }

        Log::info('SyncAllDeputiesJob completed', [
            'created' => $created,
            'updated' => $updated,
            'detail_jobs_dispatched' => count($deputyJobs),
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncAllDeputiesJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Tags para monitoramento.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return ['sync', 'deputies', 'camara-api'];
    }
}
