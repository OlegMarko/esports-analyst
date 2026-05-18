<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\RateLimitException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FaceitService
{
    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl(config('services.faceit.base_url'))
            ->withHeaders([
                'Authorization' => 'Bearer ' . config('services.faceit.key'),
                'Accept' => 'application/json',
            ])
            ->timeout(15)
            ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                if ($e instanceof \Illuminate\Http\Client\RequestException) {
                    return in_array($e->response->status(), [429, 503]);
                }

                return false;
            }, throw: false);
    }

    public function playerByNickname(string $nickname): array
    {
        $response = $this->http->get('/players', [
            'nickname' => $nickname,
            'game' => 'cs2',
        ]);

        $this->guard($response);

        return $response->json();
    }

    public function playerById(string $faceitId): array
    {
        $response = $this->http->get("/players/{$faceitId}");

        $this->guard($response);

        return $response->json();
    }

    public function playerMatches(string $faceitId, int $limit = 20, int $offset = 0): array
    {
        $response = $this->http->get("/players/{$faceitId}/history", [
            'game' => 'cs2',
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $this->guard($response);

        return $response->json('items', []);
    }

    public function matchStats(string $matchId): array
    {
        $response = $this->http->get("/matches/{$matchId}/stats");

        $this->guard($response);

        return $response->json('rounds.0', []);
    }

    public function matchDetails(string $matchId): array
    {
        $response = $this->http->get("/matches/{$matchId}");

        $this->guard($response);

        return $response->json();
    }

    private function guard(Response $response): void
    {
        if ($response->status() === 429) {
            throw new RateLimitException('Faceit API rate limit exceeded.');
        }

        if ($response->status() === 404) {
            throw new NotFoundException('Faceit resource not found.');
        }

        $response->throw();
    }
}
