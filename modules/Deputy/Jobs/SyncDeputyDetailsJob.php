<?php

declare(strict_types=1);

namespace Modules\Deputy\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Deputy\DTOs\DeputyData;
use Modules\Deputy\Models\Deputy;
use Modules\Shared\Http\CamaraApiClient;

final class SyncDeputyDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly int $externalId
    ) {}

    public function handle(CamaraApiClient $api): void
    {
        Log::info('SyncDeputyDetailsJob: Iniciando', ['external_id' => $this->externalId]);

        $detalhes = $api->getDeputado($this->externalId);

        if ($detalhes) {
            $dto = DeputyData::fromApi($detalhes);

            Deputy::updateOrCreate(
                ['external_id' => $dto->externalId],
                $dto->toArray()
            );

            Log::info('SyncDeputyDetailsJob: Finalizado', ['external_id' => $this->externalId]);
        }
    }
}
