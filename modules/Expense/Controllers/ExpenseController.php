<?php 

declare(strict_types=1);

namespace Modules\Expense\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

final class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'checkActive']);
    }

    public function index(Request $request)
    {
        //
    }
}