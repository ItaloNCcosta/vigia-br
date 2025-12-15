<?php

use Illuminate\Support\Facades\Route;
use Modules\Deputy\Controllers\DeputyController;

Route::get('/', [DeputyController::class, 'index'])->name('deputies.index');
Route::get('/search', [DeputyController::class, 'search'])->name('deputies.search');

Route::get('/ranking', [DeputyController::class, 'ranking'])->name('deputies.ranking');
Route::get('/stats', [DeputyController::class, 'stats'])->name('deputies.stats');
Route::get('/compare', [DeputyController::class, 'compare'])->name('deputies.compare');

Route::get('/estado/{stateCode}', [DeputyController::class, 'byState'])->name('deputies.by-state');
Route::get('/partido/{partyAcronym}', [DeputyController::class, 'byParty'])->name('deputies.by-party');

Route::get('/{id}', [DeputyController::class, 'show'])->name('deputies.show');
Route::get('/{id}/despesas', [DeputyController::class, 'expenses'])->name('deputies.expenses');

Route::get('/about', function () {
    return view('about');
})->name('about');
