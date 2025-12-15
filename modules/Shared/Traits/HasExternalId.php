<?php

declare(strict_types=1);

namespace Modules\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait para models que possuem ID externo da API.
 *
 * @method static Builder|static query()
 * @method static static create(array $attributes = [])
 * @method static static updateOrCreate(array $attributes, array $values = [])
 * @method static static firstOrCreate(array $attributes, array $values = [])
 */
trait HasExternalId
{
    /**
     * Busca por ID externo da API.
     */
    public static function findByExternalId(int|string $externalId): ?static
    {
        return static::query()
            ->where('external_id', $externalId)
            ->first();
    }

    /**
     * Busca por ID externo ou falha.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function findByExternalIdOrFail(int|string $externalId): static
    {
        return static::query()
            ->where('external_id', $externalId)
            ->firstOrFail();
    }

    /**
     * Busca ou cria por ID externo.
     *
     * @param int|string $externalId
     * @param array<string, mixed> $attributes
     * @return static
     */
    public static function findOrCreateByExternalId(int|string $externalId, array $attributes = []): static
    {
        return static::firstOrCreate(
            ['external_id' => $externalId],
            $attributes
        );
    }

    /**
     * Atualiza ou cria por ID externo.
     *
     * @param int|string $externalId
     * @param array<string, mixed> $attributes
     * @return static
     */
    public static function upsertByExternalId(int|string $externalId, array $attributes): static
    {
        return static::updateOrCreate(
            ['external_id' => $externalId],
            $attributes
        );
    }

    /**
     * Verifica se existe por ID externo.
     */
    public static function existsByExternalId(int|string $externalId): bool
    {
        return static::query()
            ->where('external_id', $externalId)
            ->exists();
    }

    /**
     * Scope para filtrar por IDs externos.
     *
     * @param Builder $query
     * @param array<int|string> $externalIds
     * @return Builder
     */
    public function scopeWhereExternalIdIn(Builder $query, array $externalIds): Builder
    {
        return $query->whereIn('external_id', $externalIds);
    }

    /**
     * Scope para filtrar por ID externo.
     *
     * @param Builder $query
     * @param int|string $externalId
     * @return Builder
     */
    public function scopeWhereExternalId(Builder $query, int|string $externalId): Builder
    {
        return $query->where('external_id', $externalId);
    }
}
