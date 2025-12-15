<?php

declare(strict_types=1);

namespace Modules\Deputy\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Deputy\Models\Deputy;
use Modules\Deputy\Requests\DeputyCompareRequest;
use Modules\Deputy\Requests\DeputyIndexRequest;
use Modules\Deputy\Requests\DeputySearchRequest;
use Modules\Deputy\Services\DeputyListService;
use Modules\Deputy\Services\DeputyRankingService;
use Modules\Deputy\Services\DeputyShowService;
use Modules\Shared\Enums\StateEnum;

final class DeputyController extends Controller
{
    public function __construct(
        private readonly DeputyListService $listService,
        private readonly DeputyShowService $showService,
        private readonly DeputyRankingService $rankingService
    ) {}

    public function index(DeputyIndexRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $perPage = $request->perPage();

        $deputies = $this->listService->list($filters, $perPage);

        $states = StateEnum::toSelectArray();
        $parties = $this->listService->getPartiesWithDeputies();

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $deputies->items(),
                'meta' => [
                    'current_page' => $deputies->currentPage(),
                    'last_page' => $deputies->lastPage(),
                    'per_page' => $deputies->perPage(),
                    'total' => $deputies->total(),
                ],
                'filters' => [
                    'states' => $states,
                    'parties' => $parties,
                ],
            ]);
        }

        return view('deputies.index', [
            'deputies' => $deputies,
            'filters' => $filters,
            'states' => $states,
            'parties' => $parties
        ]);
    }

    public function show(Request $request, string $id): View|JsonResponse
    {
        $deputy = $this->showService->findWithExpenseStats($id);

        if ($deputy === null) {
            abort(404, 'Deputado não encontrado');
        }

        $expensesByType = $this->showService->getExpensesByType($deputy);
        $expensesByMonth = $this->showService->getExpensesByMonth($deputy);
        $topSuppliers = $this->showService->getTopSuppliers($deputy);
        $rank = $this->rankingService->getRank($deputy);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $deputy,
                'stats' => [
                    'rank' => $rank,
                    'expenses_by_type' => $expensesByType,
                    'expenses_by_month' => $expensesByMonth,
                    'top_suppliers' => $topSuppliers,
                ],
            ]);
        }

        return view('deputies.show', [
            'deputy' => $deputy,
            'expensesByType' => $expensesByType,
            'expensesByMonth' => $expensesByMonth,
            'topSuppliers' => $topSuppliers,
            'rank' => $rank
        ]);
    }

    public function search(DeputySearchRequest $request): JsonResponse
    {
        $query = $request->searchQuery();
        $limit = $request->limit();

        $deputies = $this->listService->search($query, $limit);

        return response()->json([
            'data' => $deputies->map(fn(Deputy $d) => [
                'id' => $d->id,
                'external_id' => $d->external_id,
                'name' => $d->name,
                'electoral_name' => $d->electoral_name,
                'party_acronym' => $d->party_acronym,
                'state_code' => $d->state_code,
                'photo_url' => $d->photo_url,
            ]),
        ]);
    }

    public function ranking(Request $request): View|JsonResponse
    {
        $filters = $request->only(['state', 'party', 'year']);
        $limit = (int) $request->get('limit', 20);

        $topSpenders = $this->rankingService->topSpenders($filters, $limit);
        $lowestSpenders = $this->rankingService->lowestSpenders($filters, 10);
        $generalStats = $this->rankingService->getGeneralStats();
        $byState = $this->rankingService->averageByState();
        $byParty = $this->rankingService->averageByParty();

        $states = StateEnum::toSelectArray();
        $parties = $this->listService->getPartiesWithDeputies();

        if ($request->wantsJson()) {
            return response()->json([
                'top_spenders' => $topSpenders,
                'lowest_spenders' => $lowestSpenders,
                'stats' => $generalStats,
                'by_state' => $byState,
                'by_party' => $byParty,
                'filters' => [
                    'states' => $states,
                    'parties' => $parties,
                ],
            ]);
        }

        return view('deputies.ranking', [
            'topSpenders' => $topSpenders,
            'lowestSpenders' => $lowestSpenders,
            'generalStats' => $generalStats,
            'byState' => $byState,
            'byParty' => $byParty,
            'filters' => $filters,
            'states' => $states,
            'parties' => $parties
        ]);
    }

    public function byState(Request $request, string $stateCode): View|JsonResponse
    {
        $stateCode = strtoupper($stateCode);
        $state = StateEnum::tryFrom($stateCode);

        if ($state === null) {
            abort(404, 'Estado não encontrado');
        }

        $perPage = (int) $request->get('per_page', 20);
        $deputies = $this->listService->listByState($stateCode, $perPage);
        $topSpenders = $this->rankingService->topSpendersByState($stateCode, 10);

        if ($request->wantsJson()) {
            return response()->json([
                'state' => [
                    'code' => $state->value,
                    'name' => $state->name(),
                    'region' => $state->region(),
                    'deputy_seats' => $state->deputySeats(),
                ],
                'deputies' => $deputies,
                'top_spenders' => $topSpenders,
            ]);
        }

        return view('deputies.by-state', compact('state', 'deputies', 'topSpenders'));
    }

    public function byParty(Request $request, string $partyAcronym): View|JsonResponse
    {
        $partyAcronym = strtoupper($partyAcronym);
        $perPage = (int) $request->get('per_page', 20);

        $deputies = $this->listService->listByParty($partyAcronym, $perPage);

        if ($deputies->isEmpty()) {
            abort(404, 'Partido não encontrado ou sem deputados');
        }

        $topSpenders = $this->rankingService->topSpendersByParty($partyAcronym, 10);

        if ($request->wantsJson()) {
            return response()->json([
                'party' => $partyAcronym,
                'deputies' => $deputies,
                'top_spenders' => $topSpenders,
            ]);
        }

        return view('deputies.by-party', [
            'partyAcronym' => $partyAcronym,
            'deputies' => $deputies,
            'topSpenders' => $topSpenders
        ]);
    }

    public function compare(DeputyCompareRequest $request): View|JsonResponse
    {
        $comparison = $this->rankingService->compare(
            $request->validated('deputy1'),
            $request->validated('deputy2')
        );

        if ($comparison === null) {
            abort(404, 'Deputados não encontrados');
        }

        if ($request->wantsJson()) {
            return response()->json($comparison);
        }

        return view('deputies.compare', ['comparison' => $comparison]);
    }

    public function stats(Request $request): JsonResponse
    {
        $generalStats = $this->rankingService->getGeneralStats();
        $byState = $this->rankingService->averageByState();
        $byParty = $this->rankingService->averageByParty();
        $statesWithDeputies = $this->listService->getStatesWithDeputies();
        $partiesWithDeputies = $this->listService->getPartiesWithDeputies();

        return response()->json([
            'general' => $generalStats,
            'by_state' => $byState,
            'by_party' => $byParty,
            'states_summary' => $statesWithDeputies,
            'parties_summary' => $partiesWithDeputies,
        ]);
    }

    public function expenses(Request $request, string $id): View|JsonResponse
    {
        $deputy = $this->showService->findOrFail($id);

        $year = $request->get('year', date('Y'));

        $expensesByType = $this->showService->getExpensesByType($deputy);
        $expensesByMonth = $this->showService->getExpensesByMonth($deputy, (int) $year);
        $topSuppliers = $this->showService->getTopSuppliers($deputy, 20);

        if ($request->wantsJson()) {
            return response()->json([
                'deputy' => [
                    'id' => $deputy->id,
                    'name' => $deputy->name,
                    'total_expenses' => $deputy->total_expenses,
                ],
                'year' => $year,
                'by_type' => $expensesByType,
                'by_month' => $expensesByMonth,
                'top_suppliers' => $topSuppliers,
            ]);
        }

        return view('deputies.expenses', [
            'deputy' => $deputy,
            'year' => $year,
            'expensesByType' => $expensesByType,
            'expensesByMonth' => $expensesByMonth,
            'topSuppliers' => $topSuppliers
        ]);
    }
}
