<?php

declare(strict_types=1);

namespace Modules\Deputy\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Deputy\Jobs\EnsureDeputyDetailsJob;
use Modules\Deputy\Models\Deputy;
use Modules\Deputy\Services\FindDeputyService;
use Modules\Deputy\Services\GetExpenseYearsService;
use Modules\Deputy\Services\GetPartyOptionsService;
use Modules\Deputy\Services\ListDeputiesService;
use Modules\Deputy\Services\RankingDeputiesService;
use Modules\Expense\Jobs\EnsureDeputyRecentExpensesJob;
use Modules\Expense\Services\ListExpensesByDeputyService;
use Modules\Shared\Enums\StateEnum;

final class DeputyController extends Controller
{
    public function __construct(
        private readonly GetPartyOptionsService $getPartyOptions
    ) {}

    public function index(
        Request $request,
        ListDeputiesService $listDeputiesService
    ): View {
        $filters = $request->only(['name', 'state', 'party']);

        return view('deputies.index', [
            'deputies' => $listDeputiesService->execute($filters),
            'filters' => $filters,
            'states' => StateEnum::cases(),
            'parties' => $this->getPartyOptions->execute(),
        ]);
    }

    public function show(
        Request $request,
        Deputy $deputy,
        GetExpenseYearsService $getExpenseYearsService,
        ListExpensesByDeputyService $listExpensesByDeputyService
    ): View {
        Log::info('=== INÍCIO DO SHOW ===', ['deputy_id' => $deputy->id]);

        EnsureDeputyDetailsJob::dispatch($deputy->external_id)
            ->onQueue('ondemand');

        EnsureDeputyRecentExpensesJob::dispatch($deputy->id)
            ->onQueue('ondemand');

        Log::info('=== FIM DO SHOW ===');

        $filters = $request->only(['year']);

        return view('deputies.show', [
            'deputy' => $deputy,
            'expenses' => $listExpensesByDeputyService->execute($deputy, $filters),
            'years' => $getExpenseYearsService->execute($deputy),
            'filters' => $filters,
        ]);
    }

    public function ranking(
        Request $request,
        RankingDeputiesService $rankingDeputiesService
    ): View {
        $filters = $request->only(['name', 'state', 'party']);
        $limit = (int) $request->get('limit', 10);

        return view('deputies.ranking', [
            'deputies' => $rankingDeputiesService->execute($filters, $limit),
            'filters' => $filters,
            'states' => StateEnum::cases(),
            'parties' => $this->getPartyOptions->execute(),
        ]);
    }
}
