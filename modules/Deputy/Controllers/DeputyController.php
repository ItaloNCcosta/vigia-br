<?php

declare(strict_types=1);

namespace Modules\Deputy\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Deputy\Services\FindDeputyService;
use Modules\Deputy\Services\GetExpenseYearsService;
use Modules\Deputy\Services\GetPartyOptionsService;
use Modules\Deputy\Services\ListDeputiesService;
use Modules\Deputy\Services\RankingDeputiesService;
use Modules\Expense\Services\ListExpensesByDeputyService;
use Modules\Shared\Enums\StateEnum;

final class DeputyController extends Controller
{
    public function __construct(
        private readonly ListDeputiesService $listDeputies,
        private readonly FindDeputyService $findDeputy,
        private readonly RankingDeputiesService $rankingDeputies,
        private readonly GetPartyOptionsService $getPartyOptions,
        private readonly GetExpenseYearsService $getExpenseYears,
        private readonly ListExpensesByDeputyService $listExpensesByDeputy,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['name', 'state', 'party']);

        return view('deputies.index', [
            'deputies' => $this->listDeputies->execute($filters),
            'filters' => $filters,
            'states' => StateEnum::cases(),
            'parties' => $this->getPartyOptions->execute(),
        ]);
    }

    public function show(Request $request, string $id): View
    {
        $deputy = $this->findDeputy->execute($id);

        if (!$deputy) {
            abort(404);
        }

        $filters = $request->only(['year']);

        return view('deputies.show', [
            'deputy' => $deputy,
            'expenses' => $this->listExpensesByDeputy->execute($deputy, $filters),
            'years' => $this->getExpenseYears->execute($deputy),
            'filters' => $filters,
        ]);
    }

    public function ranking(Request $request): View
    {
        $filters = $request->only(['name', 'state', 'party']);
        $limit = (int) $request->get('limit', 10);

        return view('deputies.ranking', [
            'deputies' => $this->rankingDeputies->execute($filters, $limit),
            'filters' => $filters,
            'states' => StateEnum::cases(),
            'parties' => $this->getPartyOptions->execute(),
        ]);
    }
}
