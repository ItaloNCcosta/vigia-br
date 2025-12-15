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

    /**
     * Lista todos os deputados com filtros.
     */
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
            'parties' => $parties,
        ]);
    }

    /**
     * Exibe detalhes de um deputado com despesas.
     */
    public function show(Request $request, string $id): View|JsonResponse
    {
        $deputy = $this->showService->findWithExpenseStats($id);

        if ($deputy === null) {
            abort(404, 'Deputado não encontrado');
        }

        $expenseFilters = $request->only(['year', 'month', 'expense_type', 'supplier']);

        $expenseFilters['tab'] = $request->get('tab', 'perfil');

        $expenses = $this->showService->getExpensesPaginated($deputy, $expenseFilters, 20);

        $years = $this->showService->getAvailableYears($deputy);

        $filterMonths = $this->showService->getAvailableMonths(
            $deputy,
            $expenseFilters['year'] ?? null
        );

        $expenseTypes = $this->showService->getAvailableExpenseTypes($deputy);

        $expensesByType = $this->showService->getExpensesByType($deputy);
        $expensesByMonth = $this->showService->getExpensesByMonth($deputy);
        $topSuppliers = $this->showService->getTopSuppliers($deputy);
        $rank = $this->rankingService->getRank($deputy);

        return view('deputies.show', [
            'deputy' => $deputy,
            'expenses' => $expenses,
            'years' => $years,
            'filterMonths' => $filterMonths,
            'expenseTypes' => $expenseTypes,
            'filters' => $expenseFilters,
            'expensesByType' => $expensesByType,
            'expensesByMonth' => $expensesByMonth,
            'topSuppliers' => $topSuppliers,
            'rank' => $rank,
        ]);
    }

    /**
     * Busca deputados por nome (autocomplete/search).
     */
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

    /**
     * Ranking de maiores gastadores.
     */
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
            'parties' => $parties,
        ]);
    }

    /**
     * Deputados por estado.
     */
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

        return view('deputies.by-state', [
            'state' => $state,
            'deputies' => $deputies,
            'topSpenders' => $topSpenders,
        ]);
    }

    /**
     * Deputados por partido.
     */
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
            'topSpenders' => $topSpenders,
        ]);
    }

    /**
     * Comparativo entre dois deputados.
     */
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

        return view('deputies.compare', [
            'comparison' => $comparison,
        ]);
    }

    /**
     * Estatísticas gerais (API only).
     */
    public function stats(): JsonResponse
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

    /**
     * Despesas de um deputado (página dedicada).
     */
    public function expenses(Request $request, string $id): View|JsonResponse
    {
        $deputy = $this->showService->findOrFail($id);

        $filters = $request->only(['year', 'month', 'expense_type', 'supplier']);
        $perPage = (int) $request->get('per_page', 20);

        $expenses = $this->showService->getExpensesPaginated($deputy, $filters, $perPage);
        $years = $this->showService->getAvailableYears($deputy);
        $filterMonths = $this->showService->getAvailableMonths($deputy, $filters['year'] ?? null);
        $expenseTypes = $this->showService->getAvailableExpenseTypes($deputy);

        $expensesByType = $this->showService->getExpensesByType($deputy);
        $expensesByMonth = $this->showService->getExpensesByMonth($deputy, $filters['year'] ?? null);
        $topSuppliers = $this->showService->getTopSuppliers($deputy, 20);

        if ($request->wantsJson()) {
            return response()->json([
                'deputy' => [
                    'id' => $deputy->id,
                    'name' => $deputy->name,
                    'total_expenses' => $deputy->total_expenses,
                ],
                'expenses' => $expenses,
                'years' => $years,
                'filters' => $filters,
                'by_type' => $expensesByType,
                'by_month' => $expensesByMonth,
                'top_suppliers' => $topSuppliers,
            ]);
        }

        return view('deputies.expenses', [
            'deputy' => $deputy,
            'expenses' => $expenses,
            'years' => $years,
            'filterMonths' => $filterMonths,
            'expenseTypes' => $expenseTypes,
            'filters' => $filters,
            'expensesByType' => $expensesByType,
            'expensesByMonth' => $expensesByMonth,
            'topSuppliers' => $topSuppliers,
        ]);
    }
}
