<?php

namespace App\Ai\Tools;

use App\Services\LeaderboardService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class LeaderboardLookup implements Tool
{
    public function __construct(private string $game) {}

    public function description(): string
    {
        return 'Look up the top CS2 players leaderboard by metric on Faceit';
    }

    public function handle(Request $request): string
    {
        $results = app(LeaderboardService::class)
            ->top(
                $request['game'] ?? $this->game,
                $request['metric'],
                $request['limit'] ?? 10,
            );

        return json_encode($results);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'game' => $schema->string(),
            'metric' => $schema->string()
                ->enum(['kda', 'adr', 'frags', 'clutches_won'])
                ->required(),
            'limit' => $schema->integer()->min(1)->max(25),
        ];
    }
}
