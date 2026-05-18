<?php

namespace App\Ai\Agents;

use App\Ai\Tools\LeaderboardLookup;
use App\Ai\Tools\SimilarMatchSearch;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

class MatchAnalystAgent implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(
        private string $game = 'cs2',
    ) {}

    public function provider(): string
    {
        return (string) config('ai.agent_provider', 'ollama');
    }

    public function temperature(): float
    {
        return (float) config('ai.agent_temperature', 0.3);
    }

    public function maxTokens(): int
    {
        return (int) config('ai.agent_max_tokens', 900);
    }

    public function instructions(): string
    {
        return 'You are a professional CS2 analyst with access to Faceit match data. '
            . 'You analyse matches fetched from the Faceit Data API including per-player '
            . 'stats (KDA, ADR, HS%, clutches), map scores, and half-time splits. '
            . 'Always be specific — cite player nicknames, exact scores, and stat numbers. '
            . 'Use the similarity search tool to find historical matches with similar '
            . 'patterns before making conclusions. Keep analysis sharp and concise.';
    }

    public function messages(): iterable
    {
        return [];
    }

    public function tools(): iterable
    {
        return [
            SimilarMatchSearch::make($this->game),
            new LeaderboardLookup($this->game),
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->required(),
            'key_moments' => $schema->array()->items($schema->string())->required(),
            'mvp' => $schema->string()->required(),
            'economy_rating' => $schema->integer()->min(1)->max(10)->required(),
            'mechanical_rating' => $schema->integer()->min(1)->max(10)->required(),
            'score' => $schema->integer()->min(1)->max(10)->required(),
            'similar_match_ids' => $schema->array()->items($schema->integer()),
        ];
    }
}
