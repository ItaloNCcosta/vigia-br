<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

use DateTimeInterface;

interface SyncableInterface
{
    /**
     * Verifica se o registro está desatualizado.
     */
    public function isStale(int $minutes = 60): bool;

    /**
     * Retorna a data da última sincronização.
     */
    public function getLastSyncedAt(): ?DateTimeInterface;

    /**
     * Marca o registro como sincronizado.
     */
    public function markAsSynced(): void;

    /**
     * Atualiza ou cria registro baseado no ID externo.
     *
     * @param int|string $externalId
     * @param array<string, mixed> $attributes
     * @return static
     */
    public static function upsertFromApi(int|string $externalId, array $attributes): static;
}
