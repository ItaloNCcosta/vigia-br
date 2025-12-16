<?php

declare(strict_types=1);

namespace Modules\Expense\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Deputy\Models\Deputy;
use Modules\Expense\DTOs\ExpenseData;
use Modules\Expense\Models\Expense;
use Modules\Shared\Http\CamaraApiClient;

final class SyncDeputyExpensesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly string $deputyId,
        private readonly ?int $year = null
    ) {}

    public function handle(CamaraApiClient $api): void
    {
        $deputy = Deputy::find($this->deputyId);

        if (!$deputy) {
            return;
        }

        $year = $this->year ?? (int) date('Y');

        Log::info('SyncDeputyExpensesJob: Iniciando', [
            'deputy' => $deputy->name,
            'year' => $year,
        ]);

        $despesas = $api->getDeputadoDespesas($deputy->external_id, ['ano' => $year]);

        DB::transaction(function () use ($deputy, $despesas) {
            foreach ($despesas as $item) {
                $dto = ExpenseData::fromApi($item);

                Expense::updateOrCreate(
                    [
                        'deputy_id' => $deputy->id,
                        'external_id' => $dto->externalId,
                    ],
                    $dto->toArray()
                );
            }
        });

        Log::info('SyncDeputyExpensesJob: Finalizado', [
            'deputy' => $deputy->name,
            'total' => count($despesas),
        ]);
    }
}
