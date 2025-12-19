<?php

use Illuminate\Support\Facades\Route;
use Modules\Deputy\Controllers\DeputyController;
use Modules\Expense\Controllers\ExpenseController;

Route::get('/', fn () => redirect()->route('deputies.index'));

Route::prefix('deputados')->name('deputies.')->group(function () {
    Route::get('/', [DeputyController::class, 'index'])->name('index');
    // Route::get('/ranking', [DeputyController::class, 'ranking'])->name('ranking');
    Route::get('/{deputy}', [DeputyController::class, 'show'])->name('show');
});

Route::prefix('despesas')->name('expenses.')->group(function () {
    Route::get('/', [ExpenseController::class, 'index'])->name('index');
});

Route::view('/sobre', 'about')->name('about');
