<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\Deputy\Jobs\SyncAllDeputiesJob;
use Modules\Expense\Jobs\SyncAllExpensesJob;
use Modules\Legislature\Jobs\SyncLegislaturesJob;
use Modules\Party\Jobs\SyncPartiesJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::job(new SyncLegislaturesJob())->daily()->at('01:00');
// Schedule::job(new SyncPartiesJob())->daily()->at('01:30');

Schedule::job(new SyncAllDeputiesJob())->hourly();

Schedule::job(new SyncAllExpensesJob())->everyFifteenMinutes();
