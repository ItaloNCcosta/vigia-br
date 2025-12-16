<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

interface ApiAdapterInterface
{
    public function list(array $params = []): array;

    public function find(int $id): ?array;
}
