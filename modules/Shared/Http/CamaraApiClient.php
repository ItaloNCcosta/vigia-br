<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use GuzzleHttp\Client;

final class CamaraApiClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://dadosabertos.camara.leg.br/api/v2/',
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Cache-Control' => 'no-cache',
            ],
        ]);
    }

    public function get(string $endpoint, array $params = []): array
    {
        $response = $this->client->get($endpoint, ['query' => $params]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getDeputados(array $params = []): array
    {
        $response = $this->get('deputados', $params);

        return $response['dados'] ?? [];
    }

    public function getDeputado(int $id): ?array
    {
        $response = $this->get("deputados/{$id}");

        return $response['dados'] ?? null;
    }

    public function getDeputadoDespesas(int $id, array $params = []): array
    {
        $params['itens'] = $params['itens'] ?? 100;

        $all = [];
        $page = 1;
        $maxPages = 50;

        $params['pagina'] = $page;
        $response = $this->get("deputados/{$id}/despesas", $params);

        $all = array_merge($all, $response['dados'] ?? []);
        $links = $response['links'] ?? [];

        while ($this->hasNextPage($links) && $page < $maxPages) {
            $page++;
            $params['pagina'] = $page;

            $response = $this->get("deputados/{$id}/despesas", $params);

            $all = array_merge($all, $response['dados'] ?? []);
            $links = $response['links'] ?? [];
        }

        return $all;
    }

    public function getPartidos(array $params = []): array
    {
        $params['itens'] = $params['itens'] ?? 100;

        $response = $this->get('partidos', $params);

        return $response['dados'] ?? [];
    }

    public function getLegislaturas(array $params = []): array
    {
        $params['itens'] = $params['itens'] ?? 100;

        $response = $this->get('legislaturas', $params);

        return $response['dados'] ?? [];
    }

    public function getLegislaturaAtual(): ?array
    {
        $hoje = date('Y-m-d');
        $legislaturas = $this->getLegislaturas();

        foreach ($legislaturas as $leg) {
            $inicio = $leg['dataInicio'] ?? null;
            $fim = $leg['dataFim'] ?? null;

            if ($inicio && $fim && $hoje >= $inicio && $hoje <= $fim) {
                return $leg;
            }
        }

        return $legislaturas[0] ?? null;
    }

    private function hasNextPage(array $links): bool
    {
        foreach ($links as $link) {
            if (($link['rel'] ?? '') === 'next') {
                return true;
            }
        }

        return false;
    }
}
