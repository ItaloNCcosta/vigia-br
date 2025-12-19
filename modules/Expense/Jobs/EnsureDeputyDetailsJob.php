<?php

declare(strict_types=1);

namespace Modules\Deputy\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Deputy\Services\DeputySyncService;

final class EnsureDeputyDetailsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        private readonly int $externalId
    ) {}

    public function handle(DeputySyncService $service): void
    {
        $service->syncOneByExternalId($this->externalId);
    }
}
