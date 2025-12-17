<?php

declare(strict_types=1);

namespace Modules\Expense\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Deputy\Models\Deputy;
use Modules\Expense\DTOs\ExpenseData;
use Modules\Expense\Models\Expense;
use Modules\Shared\Http\CamaraApiClient;

final class SyncDeputyExpensesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;
    public int $maxExceptions = 3;

    public int $backoff = 10;

    public function __construct(
        private readonly string $deputyId,
        private readonly ?int $year = null
    ) {}

    public function handle(CamaraApiClient $api): void
    {
        $deputy = Deputy::select('id', 'name', 'external_id')
            ->find($this->deputyId);

        if (!$deputy) {
            Log::warning('SyncDeputyExpensesJob: Deputy not found', ['id' => $this->deputyId]);
            return;
        }

        $year = $this->year ?? (int) date('Y');

        Log::info('SyncDeputyExpensesJob: Iniciando', [
            'deputy' => $deputy->name,
            'year' => $year,
        ]);

        try {
            $despesas = $api->getDeputadoDespesas($deputy->external_id, ['ano' => $year]);

            if (empty($despesas)) {
                Log::info('SyncDeputyExpensesJob: Sem despesas', [
                    'deputy' => $deputy->name,
                    'year' => $year,
                ]);
                return;
            }


            $chunks = array_chunk($despesas, 50);
            $totalSaved = 0;

            foreach ($chunks as $chunk) {
                $dataToUpsert = [];

                foreach ($chunk as $item) {
                    $dto = ExpenseData::fromApi($item);
                    $data = $dto->toArray();
                    $data['deputy_id'] = $deputy->id;

                    $dataToUpsert[] = $data;
                }


                Expense::upsert(
                    $dataToUpsert,
                    ['deputy_id', 'external_id'],
                    array_keys($dataToUpsert[0])
                );

                $totalSaved += count($dataToUpsert);


                unset($dataToUpsert);
            }

            Log::info('SyncDeputyExpensesJob: Finalizado', [
                'deputy' => $deputy->name,
                'year' => $year,
                'total' => $totalSaved,
            ]);
        } catch (\Exception $e) {
            Log::error('SyncDeputyExpensesJob: Erro', [
                'deputy' => $deputy->name,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
