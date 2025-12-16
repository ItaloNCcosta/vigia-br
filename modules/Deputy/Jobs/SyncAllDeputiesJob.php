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
    public int $timeout = 600;

    public function handle(CamaraApiClient $api): void
    {
        $legislatura = $api->getLegislaturaAtual();
        $idLegislatura = $legislatura['id'] ?? 57;

        Log::info('SyncAllDeputiesJob: Iniciando', ['legislatura' => $idLegislatura]);

        $deputados = $api->getDeputados([
            'idLegislatura' => $idLegislatura,
            'itens' => 100,
        ]);

        DB::transaction(function () use ($api, $deputados) {
            $total = 0;

            foreach ($deputados as $item) {
                $detalhes = $api->getDeputado((int) $item['id']);

                if ($detalhes) {
                    $dto = DeputyData::fromApi($detalhes);

                    Deputy::updateOrCreate(
                        ['external_id' => $dto->externalId],
                        $dto->toArray()
                    );

                    $total++;
                }
            }

            Log::info('SyncAllDeputiesJob: Finalizado', ['total' => $total]);
        });
    }
}
