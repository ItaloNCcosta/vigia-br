<?php

declare(strict_types=1);

namespace Modules\Expense\Adapters;

use Generator;
use Modules\Shared\Http\CamaraApiClient;

final class CamaraExpenseAdapter
{
    public function __construct(
        private readonly CamaraApiClient $client
    ) {}

    public function listByDeputy(int $deputyExternalId, array $filters = []): Generator
    {
        return $this->client->paginate(
            "deputados/{$deputyExternalId}/despesas",
            $filters
        );
    }

    public function mapToModel(array $apiData, string $deputyId): array
    {
        return [
            'deputy_id' => $deputyId,
            'external_id' => $apiData['codDocumento'],
            'year' => $apiData['ano'],
            'month' => $apiData['mes'],
            'expense_type' => $apiData['tipoDespesa'] ?? null,
            'document_type' => $apiData['tipoDocumento'] ?? null,
            'document_type_code' => $apiData['codTipoDocumento'] ?? null,
            'document_number' => $apiData['numDocumento'] ?? null,
            'document_date' => $apiData['dataDocumento'] ?? null,
            'document_url' => $apiData['urlDocumento'] ?? null,
            'document_value' => $apiData['valorDocumento'] ?? 0,
            'net_value' => $apiData['valorLiquido'] ?? 0,
            'disallowed_value' => $apiData['valorGlosa'] ?? 0,
            'supplier_name' => $apiData['nomeFornecedor'] ?? null,
            'supplier_document' => $apiData['cnpjCpfFornecedor'] ?? null,
            'reimbursement_number' => $apiData['numRessarcimento'] ?? null,
            'batch_code' => $apiData['codLote'] ?? null,
            'installment' => $apiData['parcela'] ?? 0,
            'last_synced_at' => now(),
        ];
    }
}
