<?php

declare(strict_types=1);

namespace Modules\Deputy\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Deputy\Services\DeputySyncService;

final class SyncDeputyDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        private readonly int $externalId
    ) {}

    public function handle(DeputySyncService $service): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $service->syncOneByExternalId($this->externalId);
    }
}
