<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

use DateTimeInterface;

interface SyncableInterface
{
    public function isStale(int $minutes = 60): bool;

    public function getLastSyncedAt(): ?DateTimeInterface;

    public function markAsSynced(): void;

    public static function upsertFromApi(int|string $externalId, array $attributes): static;
}
