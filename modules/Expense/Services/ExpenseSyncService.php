<?php

declare(strict_types=1);

namespace Modules\Expense\Services;

use Modules\Deputy\Models\Deputy;
use Modules\Expense\Adapters\CamaraExpenseAdapter;
use Modules\Expense\Models\Expense;
use Illuminate\Support\Facades\Log;

final class ExpenseSyncService
{
    public function __construct(
        private readonly CamaraExpenseAdapter $adapter
    ) {}

    public function syncByDeputy(Deputy $deputy, array $filters = []): int
    {
        $count = 0;

        if (empty($filters)) {
            $lastDate = $deputy->expenses()->max('document_date');
            if ($lastDate) {
                $filters['dataInicio'] = $lastDate;
            }
        }

        foreach ($this->adapter->listByDeputy($deputy->external_id, $filters) as $apiExpense) {
            $this->upsertFromApi($apiExpense, $deputy->id);
            $count++;
        }

        $deputy->recalculateTotalExpenses();

        Log::info("Sincronizadas {$count} despesas do deputado {$deputy->name}");
        return $count;
    }

    public function syncByExternalId(int $externalId, array $filters = []): int
    {
        $deputy = Deputy::findByExternalId($externalId);

        if (!$deputy) {
            Log::warning("Deputado não encontrado: {$externalId}");
            return 0;
        }

        return $this->syncByDeputy($deputy, $filters);
    }

    private function upsertFromApi(array $apiData, string $deputyId): Expense
    {
        $mapped = $this->adapter->mapToModel($apiData, $deputyId);

        return Expense::updateOrCreate(
            [
                'deputy_id' => $mapped['deputy_id'],
                'external_id' => $mapped['external_id'],
            ],
            $mapped
        );
    }
}
