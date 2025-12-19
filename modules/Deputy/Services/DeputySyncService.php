<?php

declare(strict_types=1);

namespace Modules\Deputy\Services;

use Modules\Deputy\Adapters\CamaraDeputyAdapter;
use Modules\Deputy\DTOs\DeputyData;
use Modules\Deputy\Models\Deputy;

final class DeputySyncService
{
    public function __construct(
        private readonly CamaraDeputyAdapter $adapter
    ) {}

    public function syncOneByExternalId(int $externalId): void
    {
        $dto = $this->adapter->findAsDto($externalId);

        if (!$dto) {
            return;
        }

        Deputy::updateOrCreate(
            ['external_id' => $dto->externalId],
            $dto->toArray()
        );
    }

    public function syncAllFromCurrentLegislature(): array
    {
        $externalIds = [];

        foreach ($this->adapter->listCurrentDeputies() as $data) {
            $dto = DeputyData::fromApi($data);

            Deputy::updateOrCreate(
                ['external_id' => $dto->externalId],
                $dto->toArray()
            );

            $externalIds[] = $dto->externalId;
        }

        return $externalIds;
    }
}
