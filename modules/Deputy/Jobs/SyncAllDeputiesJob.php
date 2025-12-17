<?php

declare(strict_types=1);

namespace Modules\Deputy\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Deputy\DTOs\DeputyData;
use Modules\Deputy\Models\Deputy;
use Modules\Shared\Http\CamaraApiClient;

final class SyncAllDeputiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function handle(CamaraApiClient $api): void
    {
        $legislatura = $api->getLegislaturaAtual();
        $idLegislatura = $legislatura['id'] ?? 57;

        Log::info('SyncAllDeputiesJob: Iniciando', ['legislatura' => $idLegislatura]);

        $deputados = $api->getDeputados([
            'idLegislatura' => $idLegislatura,
            'itens' => 100,
        ]);

        $chunks = array_chunk($deputados, 10);
        $totalJobs = 0;

        foreach ($chunks as $index => $chunk) {
            foreach ($chunk as $item) {
                SyncDeputyDetailsJob::dispatch((int) $item['id'])
                    ->delay(now()->addSeconds($totalJobs * 2));

                $totalJobs++;
            }
        }

        Log::info('SyncAllDeputiesJob: Finalizado', [
            'total_deputies' => count($deputados),
            'jobs_dispatched' => $totalJobs
        ]);
    }
}
