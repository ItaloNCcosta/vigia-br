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

    public function syncDetails(int $externalId): ?Deputy
    {
        $data = $this->adapter->find($externalId);

        if ($data === null) {
            Log::warning('Deputy not found in API', ['external_id' => $externalId]);
            return null;
        }

        return $this->syncFromDetailData($externalId, $data);
    }

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

    public function syncStale(int $staleMinutes = 60, int $limit = 100): array
    {
        $this->resetCounters();

        $staleDeputies = Deputy::stale($staleMinutes)
            ->limit($limit)
            ->pluck('external_id')
            ->toArray();

        return $this->syncMany($staleDeputies);
    }

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

    private function syncFromDetailData(int $externalId, array $data): Deputy
    {
        $exists = Deputy::existsByExternalId($externalId);

        $deputy = Deputy::upsertFromApi($externalId, $data);

        $exists ? $this->updated++ : $this->created++;

        return $deputy;
    }

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

    public function fullSync(?callable $onProgress = null): array
    {
        return DB::transaction(function ($onProgress) {
            $stats = $this->syncAll($onProgress);
            $stats['removed'] = $this->removeStale();

            return $stats;
        });
    }

    private function resetCounters(): void
    {
        $this->created = 0;
        $this->updated = 0;
        $this->failed = 0;
    }

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
