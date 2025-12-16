<?php

declare(strict_types=1);

namespace Modules\Expense\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Expense\Services\ListExpensesService;

final class ExpenseController extends Controller
{
    public function __construct(
        private readonly ListExpensesService $listExpenses,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['start', 'end', 'type', 'supplier']);

        return view('expenses.index', [
            'expenses' => $this->listExpenses->execute($filters),
            'filters' => $filters,
        ]);
    }
}