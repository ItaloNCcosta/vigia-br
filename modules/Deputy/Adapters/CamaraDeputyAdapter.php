<?php

declare(strict_types=1);

namespace Modules\Deputy\Adapters;

use Generator;
use Modules\Deputy\DTOs\DeputyData;
use Modules\Shared\Contracts\ApiAdapterInterface;
use Modules\Shared\Http\CamaraApiClient;

final class CamaraDeputyAdapter implements ApiAdapterInterface
{
    private const ENDPOINT = 'deputados';

    public function __construct(
        private readonly CamaraApiClient $client
    ) {}

    public function list(array $filters = []): array
    {
        return $this->client->cached(self::ENDPOINT, $this->normalizeFilters($filters));
    }

    public function find(int|string $externalId): ?array
    {
        $response = $this->client->get(self::ENDPOINT . "/{$externalId}");

        return $response['dados'] ?? null;
    }

    public function findAsDto(int|string $externalId): ?DeputyData
    {
        $data = $this->find($externalId);

        if ($data === null) {
            return null;
        }

        return DeputyData::fromApi($data);
    }

    public function paginate(array $filters = []): Generator
    {
        return $this->client->paginate(self::ENDPOINT, $this->normalizeFilters($filters));
    }

    public function paginateAsDto(array $filters = []): Generator
    {
        foreach ($this->paginate($filters) as $data) {
            yield DeputyData::fromApi($data);
        }
    }

    public function listCurrentDeputies(): Generator
    {
        return $this->paginate([
            'idLegislatura' => $this->getCurrentLegislatureId(),
            'ordem' => 'ASC',
            'ordenarPor' => 'nome',
        ]);
    }

    public function listExpenses(string $deputyId, array $filters = []): Generator
    {
        return $this->client->paginate(
            self::ENDPOINT . "/{$deputyId}/despesas",
            $this->normalizeExpenseFilters($filters)
        );
    }

    public function listExpensesByYear(string $deputyId, int $year): Generator
    {
        return $this->listExpenses($deputyId, ['ano' => $year]);
    }

    public function listExpensesByMonth(string $deputyId, int $year, int $month): Generator
    {
        return $this->listExpenses($deputyId, [
            'ano' => $year,
            'mes' => $month,
        ]);
    }

    public function count(array $filters = []): int
    {
        return $this->client->count(self::ENDPOINT, $this->normalizeFilters($filters));
    }

    private function getCurrentLegislatureId(): int
    {
        $year = (int) date('Y');

        if ($year >= 2027) {
            return 58;
        }

        return 57;
    }

    private function normalizeFilters(array $filters): array
    {
        $normalized = [];

        $mapping = [
            'name' => 'nome',
            'nome' => 'nome',
            'state' => 'siglaUf',
            'siglaUf' => 'siglaUf',
            'party' => 'siglaPartido',
            'siglaPartido' => 'siglaPartido',
            'legislature' => 'idLegislatura',
            'idLegislatura' => 'idLegislatura',
            'page' => 'pagina',
            'pagina' => 'pagina',
            'perPage' => 'itens',
            'itens' => 'itens',
            'order' => 'ordem',
            'ordem' => 'ordem',
            'orderBy' => 'ordenarPor',
            'ordenarPor' => 'ordenarPor',
        ];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $normalizedKey = $mapping[$key] ?? $key;
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    private function normalizeExpenseFilters(array $filters): array
    {
        $normalized = [];

        $mapping = [
            'year' => 'ano',
            'ano' => 'ano',
            'month' => 'mes',
            'mes' => 'mes',
            'supplier' => 'cnpjCpfFornecedor',
            'cnpjCpfFornecedor' => 'cnpjCpfFornecedor',
            'page' => 'pagina',
            'pagina' => 'pagina',
            'perPage' => 'itens',
            'itens' => 'itens',
            'order' => 'ordem',
            'ordem' => 'ordem',
            'orderBy' => 'ordenarPor',
            'ordenarPor' => 'ordenarPor',
        ];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $normalizedKey = $mapping[$key] ?? $key;
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }
}
