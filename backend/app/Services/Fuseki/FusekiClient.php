<?php

namespace App\Services\Fuseki;

use App\Exceptions\FusekiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Petit client HTTP pour Apache Jena Fuseki (protocole SPARQL 1.1).
 *   POST {url}/{dataset}/query   -> SELECT / ASK
 *   POST {url}/{dataset}/update  -> INSERT / DELETE ...
 *   PUT  {url}/{dataset}/data    -> remplace le graphe par défaut (Graph Store Protocol)
 *   GET  {url}/{dataset}/data    -> export du graphe
 */
class FusekiClient
{
    private string $base;
    private ?string $user;
    private ?string $password;
    private int $timeout;

    public function __construct(
        ?string $url = null,
        ?string $dataset = null,
        ?string $user = null,
        ?string $password = null,
        ?int $timeout = null,
    ) {
        $url ??= config('fuseki.url', 'http://localhost:3030');
        $dataset ??= config('fuseki.dataset', 'famille');
        $this->user = $user ?? config('fuseki.user');
        $this->password = $password ?? config('fuseki.password');
        $this->timeout = $timeout ?? (int) config('fuseki.timeout', 15);
        $this->base = rtrim($url, '/') . '/' . trim($dataset, '/');
    }

    /** Exécute un SELECT (ou un ASK) et renvoie le JSON SPARQL décodé. */
    public function select(string $query): array
    {
        $response = $this->send(fn ($http) => $http
            ->withHeaders(['Accept' => 'application/sparql-results+json'])
            ->asForm()
            ->post($this->base . '/query', ['query' => $query]));

        $this->assertOk($response, 'Requête SPARQL refusée par Fuseki');

        return $response->json() ?? [];
    }

    /** Exécute un ASK et renvoie un booléen. */
    public function ask(string $query): bool
    {
        return (bool) ($this->select($query)['boolean'] ?? false);
    }

    /** Exécute une mise à jour SPARQL (INSERT DATA, DELETE WHERE, ...). */
    public function update(string $update): void
    {
        $response = $this->send(fn ($http) => $http
            ->asForm()
            ->post($this->base . '/update', ['update' => $update]));

        $this->assertOk($response, 'Mise à jour SPARQL refusée par Fuseki (le dataset a-t-il été lancé avec --update ?)');
    }

    /** Remplace tout le contenu du graphe par défaut par le Turtle fourni. */
    public function replaceWithTurtle(string $turtle): void
    {
        $response = $this->send(fn ($http) => $http
            ->withBody($turtle, 'text/turtle; charset=utf-8')
            ->put($this->base . '/data'));

        $this->assertOk($response, "Chargement de l'ontologie refusé par Fuseki");
    }

    /** Exporte le graphe par défaut au format Turtle. */
    public function exportTurtle(): string
    {
        $response = $this->send(fn ($http) => $http
            ->withHeaders(['Accept' => 'text/turtle'])
            ->get($this->base . '/data'));

        $this->assertOk($response, 'Export impossible');

        return $response->body();
    }

    /** true si Fuseki répond et que le dataset existe. */
    public function isReachable(): bool
    {
        try {
            $this->ask('ASK { }');
            return true;
        } catch (FusekiException) {
            return false;
        }
    }

    public function endpoint(): string
    {
        return $this->base;
    }

    // ------------------------------------------------------------------

    private function send(callable $call)
    {
        $http = Http::timeout($this->timeout);
        if ($this->user) {
            $http = $http->withBasicAuth($this->user, (string) $this->password);
        }

        try {
            return $call($http);
        } catch (ConnectionException $e) {
            throw new FusekiException(
                "Impossible de joindre Fuseki ({$this->base}). Le serveur est-il lancé ?",
                503,
                $e->getMessage(),
                $e
            );
        }
    }

    private function assertOk($response, string $message): void
    {
        if ($response->failed()) {
            $status = $response->status();
            // 400 = requête SPARQL invalide -> erreur côté appelant (422)
            throw new FusekiException(
                $message,
                $status === 400 ? 422 : 502,
                mb_substr(trim((string) $response->body()), 0, 600)
            );
        }
    }
}
