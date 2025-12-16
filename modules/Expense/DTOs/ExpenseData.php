<?php

declare(strict_types=1);

namespace Modules\Expense\DTOs;

final readonly class ExpenseData
{
    public function __construct(
        public int $externalId,
        public int $year,
        public int $month,
        public ?string $expenseType,
        public ?string $documentType,
        public ?int $documentTypeCode,
        public ?string $documentNumber,
        public ?string $documentDate,
        public ?string $documentUrl,
        public float $documentValue,
        public float $netValue,
        public float $disallowedValue,
        public ?string $supplierName,
        public ?string $supplierDocument,
        public ?string $reimbursementNumber,
        public ?int $batchCode,
        public int $installment,
    ) {}

    public static function fromApi(array $data): self
    {
        return new self(
            externalId: (int) $data['codDocumento'],
            year: (int) $data['ano'],
            month: (int) $data['mes'],
            expenseType: $data['tipoDespesa'] ?? null,
            documentType: $data['tipoDocumento'] ?? null,
            documentTypeCode: isset($data['codTipoDocumento']) ? (int) $data['codTipoDocumento'] : null,
            documentNumber: $data['numDocumento'] ?? null,
            documentDate: $data['dataDocumento'] ?? null,
            documentUrl: $data['urlDocumento'] ?? null,
            documentValue: (float) ($data['valorDocumento'] ?? 0),
            netValue: (float) ($data['valorLiquido'] ?? 0),
            disallowedValue: (float) ($data['valorGlosa'] ?? 0),
            supplierName: $data['nomeFornecedor'] ?? null,
            supplierDocument: $data['cnpjCpfFornecedor'] ?? null,
            reimbursementNumber: $data['numRessarcimento'] ?? null,
            batchCode: isset($data['codLote']) ? (int) $data['codLote'] : null,
            installment: (int) ($data['parcela'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'year' => $this->year,
            'month' => $this->month,
            'expense_type' => $this->expenseType,
            'document_type' => $this->documentType,
            'document_type_code' => $this->documentTypeCode,
            'document_number' => $this->documentNumber,
            'document_date' => $this->documentDate,
            'document_url' => $this->documentUrl,
            'document_value' => $this->documentValue,
            'net_value' => $this->netValue,
            'disallowed_value' => $this->disallowedValue,
            'supplier_name' => $this->supplierName,
            'supplier_document' => $this->supplierDocument,
            'reimbursement_number' => $this->reimbursementNumber,
            'batch_code' => $this->batchCode,
            'installment' => $this->installment,
            'last_synced_at' => now(),
        ];
    }
}
