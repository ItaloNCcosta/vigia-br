<?php

declare(strict_types=1);

namespace Modules\Deputy\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Deputy\Services\DeputySyncService;

final class SyncDeputiesBatchJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(DeputySyncService $service): void
    {
        $externalIds = $service->syncAllFromCurrentLegislature();

        $jobs = array_map(
            fn(int $id) => new SyncDeputyDetailsJob($id),
            $externalIds
        );

        Bus::batch($jobs)
            ->name('sync-deputy-details')
            ->onQueue('heavy')
            ->dispatch();
    }
}
