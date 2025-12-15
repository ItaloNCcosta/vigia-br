<?php

declare(strict_types=1);

namespace Modules\Deputy\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Deputy\Adapters\CamaraDeputyAdapter;
use Modules\Deputy\DTOs\DeputyData;
use Modules\Deputy\Models\Deputy;

final class DeputySyncService
{
    private int $created = 0;
    private int $updated = 0;
    private int $failed = 0;

    public function __construct(
        private readonly CamaraDeputyAdapter $adapter
    ) {}

    /**
     * Sincroniza todos os deputados da legislatura atual.
     *
     * @param callable|null $onProgress Callback para progresso (recebe: current, total, deputy)
     * @return array<string, int>
     */
    public function syncAll(?callable $onProgress = null): array
    {
        $this->resetCounters();

        Log::info('Starting full deputies sync');

        foreach ($this->adapter->listCurrentDeputies() as $index => $data) {
            try {
                $deputy = $this->syncFromListData($data);

                if ($onProgress) {
                    $onProgress($index + 1, null, $deputy);
                }
            } catch (\Throwable $e) {
                $this->failed++;
                Log::error('Failed to sync deputy', [
                    'external_id' => $data['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Deputies sync completed', $this->getStats());

        return $this->getStats();
    }

    /**
     * Sincroniza detalhes completos de um deputado.
     */
    public function syncDetails(int $externalId): ?Deputy
    {
        $data = $this->adapter->find($externalId);

        if ($data === null) {
            Log::warning('Deputy not found in API', ['external_id' => $externalId]);
            return null;
        }

        return $this->syncFromDetailData($externalId, $data);
    }

    /**
     * Sincroniza múltiplos deputados por IDs externos.
     *
     * @param array<int> $externalIds
     * @return array<string, int>
     */
    public function syncMany(array $externalIds): array
    {
        $this->resetCounters();

        foreach ($externalIds as $externalId) {
            try {
                $this->syncDetails($externalId);
            } catch (\Throwable $e) {
                $this->failed++;
                Log::error('Failed to sync deputy details', [
                    'external_id' => $externalId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->getStats();
    }

    /**
     * Sincroniza deputados desatualizados.
     *
     * @param int $staleMinutes
     * @param int $limit
     * @return array<string, int>
     */
    public function syncStale(int $staleMinutes = 60, int $limit = 100): array
    {
        $this->resetCounters();

        $staleDeputies = Deputy::stale($staleMinutes)
            ->limit($limit)
            ->pluck('external_id')
            ->toArray();

        return $this->syncMany($staleDeputies);
    }

    /**
     * Sincroniza a partir de dados da listagem (básico).
     *
     * @param array<string, mixed> $data
     */
    private function syncFromListData(array $data): Deputy
    {
        $externalId = (int) $data['id'];
        $exists = Deputy::existsByExternalId($externalId);

        $deputy = Deputy::upsertByExternalId($externalId, [
            'name' => $data['nome'] ?? '',
            'electoral_name' => $data['nome'] ?? null,
            'state_code' => $data['siglaUf'] ?? '',
            'party_acronym' => $data['siglaPartido'] ?? '',
            'email' => $data['email'] ?? null,
            'photo_url' => $data['urlFoto'] ?? null,
            'uri' => $data['uri'] ?? null,
            'last_synced_at' => now(),
        ]);

        $exists ? $this->updated++ : $this->created++;

        return $deputy;
    }

    /**
     * Sincroniza a partir de dados detalhados.
     *
     * @param int $externalId
     * @param array<string, mixed> $data
     */
    private function syncFromDetailData(int $externalId, array $data): Deputy
    {
        $exists = Deputy::existsByExternalId($externalId);

        $deputy = Deputy::upsertFromApi($externalId, $data);

        $exists ? $this->updated++ : $this->created++;

        return $deputy;
    }

    /**
     * Remove deputados que não estão mais na API.
     *
     * @return int Número de removidos
     */
    public function removeStale(): int
    {
        $apiExternalIds = [];

        foreach ($this->adapter->listCurrentDeputies() as $data) {
            $apiExternalIds[] = (int) $data['id'];
        }

        $removed = Deputy::whereNotIn('external_id', $apiExternalIds)->delete();

        Log::info('Removed stale deputies', ['count' => $removed]);

        return $removed;
    }

    /**
     * Sincronização completa com remoção de antigos.
     *
     * @param callable|null $onProgress
     * @return array<string, int>
     */
    public function fullSync(?callable $onProgress = null): array
    {
        DB::beginTransaction();

        try {
            $stats = $this->syncAll($onProgress);
            $stats['removed'] = $this->removeStale();

            DB::commit();

            return $stats;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reseta contadores.
     */
    private function resetCounters(): void
    {
        $this->created = 0;
        $this->updated = 0;
        $this->failed = 0;
    }

    /**
     * Retorna estatísticas.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'failed' => $this->failed,
            'total' => $this->created + $this->updated,
        ];
    }
}
