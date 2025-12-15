<?php

declare(strict_types=1);

namespace Modules\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasExternalId
{
    public static function findByExternalId(int|string $externalId): ?static
    {
        return static::query()
            ->where('external_id', $externalId)
            ->first();
    }

    public static function findByExternalIdOrFail(int|string $externalId): static
    {
        return static::query()
            ->where('external_id', $externalId)
            ->firstOrFail();
    }

    public static function findOrCreateByExternalId(int|string $externalId, array $attributes = []): static
    {
        return static::firstOrCreate(
            ['external_id' => $externalId],
            $attributes
        );
    }

    public static function upsertByExternalId(int|string $externalId, array $attributes): static
    {
        return static::updateOrCreate(
            ['external_id' => $externalId],
            $attributes
        );
    }

    public static function existsByExternalId(int|string $externalId): bool
    {
        return static::query()
            ->where('external_id', $externalId)
            ->exists();
    }

    public function scopeWhereExternalIdIn(Builder $query, array $externalIds): Builder
    {
        return $query->whereIn('external_id', $externalIds);
    }

    public function scopeWhereExternalId(Builder $query, int|string $externalId): Builder
    {
        return $query->where('external_id', $externalId);
    }
}
