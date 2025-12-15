<?php

declare(strict_types=1);

namespace Modules\Deputy\Services;

use Modules\Deputy\Adapters\CamaraDeputyAdapter;
use Modules\Deputy\Jobs\SyncDeputyDetailsJob;
use Modules\Deputy\Models\Deputy;

final class DeputyShowService
{
    public function __construct(
        private readonly CamaraDeputyAdapter $adapter
    ) {}

    /**
     * Busca deputado por ID (UUID).
     */
    public function find(string $id): ?Deputy
    {
        return Deputy::with(['party', 'legislature'])->find($id);
    }

    /**
     * Busca deputado por ID ou falha.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $id): Deputy
    {
        return Deputy::with(['party', 'legislature'])->findOrFail($id);
    }

    /**
     * Busca deputado por ID externo da API.
     */
    public function findByExternalId(int $externalId): ?Deputy
    {
        return Deputy::with(['party', 'legislature'])
            ->findByExternalId($externalId);
    }

    /**
     * Busca deputado por ID externo ou falha.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findByExternalIdOrFail(int $externalId): Deputy
    {
        return Deputy::with(['party', 'legislature'])
            ->findByExternalIdOrFail($externalId);
    }

    /**
     * Busca deputado e atualiza se estiver desatualizado.
     */
    public function findAndRefreshIfStale(string $id, int $staleMinutes = 60): ?Deputy
    {
        $deputy = $this->find($id);

        if ($deputy === null) {
            return null;
        }

        if ($deputy->isStale($staleMinutes)) {
            $this->refreshFromApi($deputy);
        }

        return $deputy->fresh(['party', 'legislature']);
    }

    /**
     * Busca deputado por ID externo e atualiza se necessário.
     */
    public function findByExternalIdAndRefresh(int $externalId, int $staleMinutes = 60): ?Deputy
    {
        $deputy = $this->findByExternalId($externalId);

        if ($deputy === null) {
            // Tenta buscar na API e criar
            return $this->fetchAndCreateFromApi($externalId);
        }

        if ($deputy->isStale($staleMinutes)) {
            $this->refreshFromApi($deputy);
        }

        return $deputy->fresh(['party', 'legislature']);
    }

    /**
     * Atualiza deputado a partir da API.
     */
    public function refreshFromApi(Deputy $deputy): void
    {
        $data = $this->adapter->find($deputy->external_id);

        if ($data !== null) {
            Deputy::upsertFromApi($deputy->external_id, $data);
        }
    }

    /**
     * Dispara job de atualização em background.
     */
    public function refreshFromApiAsync(Deputy $deputy): void
    {
        SyncDeputyDetailsJob::dispatch($deputy->external_id);
    }

    /**
     * Busca deputado na API e cria no banco.
     */
    public function fetchAndCreateFromApi(int $externalId): ?Deputy
    {
        $data = $this->adapter->find($externalId);

        if ($data === null) {
            return null;
        }

        return Deputy::upsertFromApi($externalId, $data);
    }

    /**
     * Retorna deputado com estatísticas de despesas.
     */
    public function findWithExpenseStats(string $id): ?Deputy
    {
        return Deputy::with(['party', 'legislature'])
            ->withSum('expenses', 'net_value')
            ->withCount('expenses')
            ->find($id);
    }

    /**
     * Retorna resumo de despesas por tipo.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getExpensesByType(Deputy $deputy): \Illuminate\Support\Collection
    {
        return $deputy->expenses()
            ->select('expense_type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(net_value) as total')
            ->groupBy('expense_type')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Retorna resumo de despesas por mês.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getExpensesByMonth(Deputy $deputy, ?int $year = null): \Illuminate\Support\Collection
    {
        return $deputy->expenses()
            ->select('year', 'month')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(net_value) as total')
            ->when($year, fn ($q) => $q->where('year', $year))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    /**
     * Retorna maiores fornecedores do deputado.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getTopSuppliers(Deputy $deputy, int $limit = 10): \Illuminate\Support\Collection
    {
        return $deputy->expenses()
            ->select('supplier_name', 'supplier_document')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(net_value) as total')
            ->whereNotNull('supplier_name')
            ->groupBy('supplier_name', 'supplier_document')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }
}
