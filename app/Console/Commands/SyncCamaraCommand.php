<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Deputy\Jobs\SyncAllDeputiesJob;
use Modules\Expense\Jobs\SyncAllExpensesJob;

final class SyncCamaraCommand extends Command
{
    protected $signature = 'camara:sync 
                            {--deputies : Sincroniza apenas deputados}
                            {--expenses : Sincroniza apenas despesas}
                            {--year= : Ano específico das despesas (padrão: todos os anos da legislatura atual)}';

    protected $description = 'Sincroniza dados da Câmara dos Deputados';

    public function handle(): int
    {
        $onlyDeputies = $this->option('deputies');
        $onlyExpenses = $this->option('expenses');
        $year = $this->option('year') ? (int) $this->option('year') : null;

        if (!$onlyDeputies && !$onlyExpenses) {
            $onlyDeputies = true;
            $onlyExpenses = true;
        }

        if ($onlyDeputies) {
            $this->info('Sincronizando deputados da legislatura atual...');
            SyncAllDeputiesJob::dispatchSync();
            $this->info('Deputados sincronizados!');
        }

        if ($onlyExpenses) {
            if ($year) {
                $this->info("Sincronizando despesas do ano {$year}...");
            } else {
                $this->info('Sincronizando despesas de todos os anos da legislatura atual...');
            }
            $this->warn('Isso pode demorar vários minutos...');
            SyncAllExpensesJob::dispatchSync($year);
            $this->info('Jobs de despesas disparados!');
        }

        $this->newLine();
        $this->info('Sincronização concluída!');

        return self::SUCCESS;
    }
}
