<?php

declare(strict_types=1);

namespace Modules\Shared\Traits;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

trait HasSyncStatus
{
    public function isStale(int $minutes = 60): bool
    {
        if ($this->last_synced_at === null) {
            return true;
        }

        return $this->last_synced_at->lt(now()->subMinutes($minutes));
    }

    public function isFresh(int $minutes = 60): bool
    {
        return !$this->isStale($minutes);
    }

    public function getLastSyncedAt(): ?DateTimeInterface
    {
        return $this->last_synced_at;
    }

    public function markAsSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }

    public function markAsStale(): void
    {
        $this->update(['last_synced_at' => null]);
    }

    public function scopeStale(Builder $query, int $minutes = 60): Builder
    {
        return $query->where(function (Builder $q) use ($minutes) {
            $q->whereNull('last_synced_at')
                ->orWhere('last_synced_at', '<', now()->subMinutes($minutes));
        });
    }

    public function scopeFresh(Builder $query, int $minutes = 60): Builder
    {
        return $query->where('last_synced_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeNeverSynced(Builder $query): Builder
    {
        return $query->whereNull('last_synced_at');
    }

    public function scopeSyncedAfter(Builder $query, DateTimeInterface $date): Builder
    {
        return $query->where('last_synced_at', '>=', $date);
    }
}
