<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

use Generator;

interface ApiAdapterInterface
{
    public function list(array $filters = []): array;

    public function find(int|string $externalId): ?array;

    public function paginate(array $filters = []): Generator;
}
