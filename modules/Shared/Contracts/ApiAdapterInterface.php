<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

use Generator;

interface ApiAdapterInterface
{
    /**
     * Lista recursos com filtros.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array;

    /**
     * Busca um recurso específico por ID externo.
     */
    public function find(int|string $externalId): ?array;

    /**
     * Retorna um generator para paginação automática.
     *
     * @param array<string, mixed> $filters
     * @return Generator<int, array<string, mixed>>
     */
    public function paginate(array $filters = []): Generator;
}
