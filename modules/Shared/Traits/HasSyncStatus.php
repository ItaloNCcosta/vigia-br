<?php

declare(strict_types=1);

namespace Modules\Shared\Traits;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait para models que precisam de controle de sincronização.
 *
 * Requer coluna `last_synced_at` (timestamp nullable) na tabela.
 *
 * @property \Carbon\Carbon|null $last_synced_at
 * @method static Builder|static query()
 */
trait HasSyncStatus
{
    /**
     * Verifica se o registro está desatualizado.
     *
     * @param int $minutes Minutos para considerar desatualizado
     */
    public function isStale(int $minutes = 60): bool
    {
        if ($this->last_synced_at === null) {
            return true;
        }

        return $this->last_synced_at->lt(now()->subMinutes($minutes));
    }

    /**
     * Verifica se o registro está atualizado.
     *
     * @param int $minutes Minutos para considerar atualizado
     */
    public function isFresh(int $minutes = 60): bool
    {
        return !$this->isStale($minutes);
    }

    /**
     * Retorna a data da última sincronização.
     */
    public function getLastSyncedAt(): ?DateTimeInterface
    {
        return $this->last_synced_at;
    }

    /**
     * Marca o registro como sincronizado agora.
     */
    public function markAsSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }

    /**
     * Marca o registro como não sincronizado.
     */
    public function markAsStale(): void
    {
        $this->update(['last_synced_at' => null]);
    }

    /**
     * Scope para registros desatualizados.
     *
     * @param Builder $query
     * @param int $minutes
     * @return Builder
     */
    public function scopeStale(Builder $query, int $minutes = 60): Builder
    {
        return $query->where(function (Builder $q) use ($minutes) {
            $q->whereNull('last_synced_at')
                ->orWhere('last_synced_at', '<', now()->subMinutes($minutes));
        });
    }

    /**
     * Scope para registros atualizados.
     *
     * @param Builder $query
     * @param int $minutes
     * @return Builder
     */
    public function scopeFresh(Builder $query, int $minutes = 60): Builder
    {
        return $query->where('last_synced_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope para registros nunca sincronizados.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeNeverSynced(Builder $query): Builder
    {
        return $query->whereNull('last_synced_at');
    }

    /**
     * Scope para registros sincronizados após uma data.
     *
     * @param Builder $query
     * @param DateTimeInterface $date
     * @return Builder
     */
    public function scopeSyncedAfter(Builder $query, DateTimeInterface $date): Builder
    {
        return $query->where('last_synced_at', '>=', $date);
    }
}
