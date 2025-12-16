<?php

declare(strict_types=1);

namespace Modules\Shared\Traits;

trait HasSyncStatus
{
    public function isStale(int $minutes = 60): bool
    {
        if ($this->last_synced_at === null) {
            return true;
        }

        return $this->last_synced_at->diffInMinutes(now()) > $minutes;
    }

    public function markAsSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }
}
