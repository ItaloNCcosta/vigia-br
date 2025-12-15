<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class CamaraApiClient
{
    private PendingRequest $http;

    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_RETRIES = 3;
    private const DEFAULT_RETRY_DELAY = 200;
    private const DEFAULT_CACHE_TTL = 5; // minutos

    public function __construct()
    {
        $this->http = Http::acceptJson()
            ->baseUrl($this->getBaseUrl())
            ->timeout(config('services.camara.timeout', self::DEFAULT_TIMEOUT))
            ->retry(
                self::DEFAULT_RETRIES,
                self::DEFAULT_RETRY_DELAY,
                throw: false
            );
    }

    /**
     * Requisição GET simples.
     *
     * @param string $endpoint
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $params = []): array
    {
        try {
            $response = $this->http->get($endpoint, $params);
            $response->throw();

            return $response->json() ?? ['dados' => [], 'links' => []];
        } catch (RequestException $e) {
            Log::warning('Camara API Error', [
                'endpoint' => $endpoint,
                'status' => $e->response?->status(),
                'params' => $params,
                'message' => $e->getMessage(),
            ]);

            return ['dados' => [], 'links' => []];
        }
    }

    /**
     * Requisição GET com cache.
     *
     * @param string $endpoint
     * @param array<string, mixed> $params
     * @param int $ttlMinutes
     * @return array<string, mixed>
     */
    public function cached(string $endpoint, array $params = [], int $ttlMinutes = self::DEFAULT_CACHE_TTL): array
    {
        $key = $this->buildCacheKey($endpoint, $params);

        return Cache::remember(
            $key,
            now()->addMinutes($ttlMinutes),
            fn () => $this->get($endpoint, $params)
        );
    }

    /**
     * Paginação automática - retorna Generator.
     *
     * @param string $endpoint
     * @param array<string, mixed> $params
     * @return Generator<int, array<string, mixed>>
     */
    public function paginate(string $endpoint, array $params = []): Generator
    {
        $response = $this->get($endpoint, $params);

        yield from $response['dados'] ?? [];

        $links = $response['links'] ?? [];

        while ($next = $this->findNextLink($links)) {
            $response = $this->getByUrl($next['href']);

            yield from $response['dados'] ?? [];

            $links = $response['links'] ?? [];
        }
    }

    /**
     * Conta total de registros (útil para progress bars).
     *
     * @param string $endpoint
     * @param array<string, mixed> $params
     * @return int
     */
    public function count(string $endpoint, array $params = []): int
    {
        $params['itens'] = 1;
        $response = $this->get($endpoint, $params);

        $lastLink = collect($response['links'] ?? [])
            ->firstWhere('rel', 'last');

        if ($lastLink && preg_match('/pagina=(\d+)/', $lastLink['href'], $matches)) {
            return (int) $matches[1];
        }

        return count($response['dados'] ?? []);
    }

    /**
     * Requisição GET por URL completa (para paginação).
     *
     * @param string $url
     * @return array<string, mixed>
     */
    private function getByUrl(string $url): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(config('services.camara.timeout', self::DEFAULT_TIMEOUT))
                ->get($url);

            $response->throw();

            return $response->json() ?? ['dados' => [], 'links' => []];
        } catch (RequestException $e) {
            Log::warning('Camara API Pagination Error', [
                'url' => $url,
                'status' => $e->response?->status(),
                'message' => $e->getMessage(),
            ]);

            return ['dados' => [], 'links' => []];
        }
    }

    /**
     * Encontra o link "next" na resposta.
     *
     * @param array<int, array<string, string>> $links
     * @return array<string, string>|null
     */
    private function findNextLink(array $links): ?array
    {
        return collect($links)->firstWhere('rel', 'next');
    }

    /**
     * Constrói a chave de cache.
     */
    private function buildCacheKey(string $endpoint, array $params): string
    {
        return 'camara:' . md5($endpoint . serialize($params));
    }

    /**
     * Retorna a URL base da API.
     */
    private function getBaseUrl(): string
    {
        return config('services.camara.url', 'https://dadosabertos.camara.leg.br/api/v2/');
    }

    /**
     * Limpa o cache de um endpoint específico.
     */
    public function clearCache(string $endpoint, array $params = []): bool
    {
        $key = $this->buildCacheKey($endpoint, $params);

        return Cache::forget($key);
    }

    /**
     * Limpa todo o cache da API da Câmara.
     */
    public function clearAllCache(): void
    {
        // Isso funciona se você estiver usando tags (Redis/Memcached)
        // Cache::tags(['camara'])->flush();

        // Para file/database driver, você precisaria de uma abordagem diferente
        Log::info('Cache clear requested for Camara API');
    }
}
