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
use Modules\Shared\Http\CamaraApiClient;

final class SyncAllExpensesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600;

    public function __construct(
        private readonly ?int $year = null
    ) {}

    public function handle(CamaraApiClient $api): void
    {
        $years = $this->getYearsToSync($api);

        Log::info('SyncAllExpensesJob: Iniciando', ['years' => $years]);

        $deputies = Deputy::all();

        foreach ($deputies as $deputy) {
            foreach ($years as $year) {
                SyncDeputyExpensesJob::dispatch($deputy->id, $year);
            }
        }

        Log::info('SyncAllExpensesJob: Jobs disparados', [
            'deputies' => $deputies->count(),
            'years' => count($years),
            'total_jobs' => $deputies->count() * count($years),
        ]);
    }

    private function getYearsToSync(CamaraApiClient $api): array
    {
        if ($this->year) {
            return [$this->year];
        }

        $legislatura = $api->getLegislaturaAtual();

        if (!$legislatura) {
            return [(int) date('Y')];
        }

        $anoInicio = (int) substr($legislatura['dataInicio'], 0, 4);
        $anoAtual = (int) date('Y');

        return range($anoInicio, $anoAtual);
    }
}
