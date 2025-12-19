<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\Deputy\Jobs\SyncDeputiesBatchJob;
use Modules\Expense\Jobs\SyncDeputyExpensesBatchJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SyncDeputiesBatchJob())
    ->dailyAt('03:00');

Schedule::job(new SyncDeputyExpensesBatchJob())
    ->hourly();
