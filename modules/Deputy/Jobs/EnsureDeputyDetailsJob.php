<?php

declare(strict_types=1);

namespace Modules\Deputy\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Deputy\Services\DeputySyncService;

final class EnsureDeputyDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        private readonly int $externalId
    ) {
        Log::info('EnsureDeputyDetailsJob criado', ['external_id' => $externalId]);
    }

    public function handle(DeputySyncService $service): void
    {
        Log::info('EnsureDeputyDetailsJob iniciado', ['external_id' => $this->externalId]);

        $service->syncOneByExternalId($this->externalId);

        Log::info('EnsureDeputyDetailsJob finalizado', ['external_id' => $this->externalId]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EnsureDeputyDetailsJob falhou', [
            'external_id' => $this->externalId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
