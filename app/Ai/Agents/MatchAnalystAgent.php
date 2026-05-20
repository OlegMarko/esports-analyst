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

    public static function forCurrentEnv(string $game): static
    {
        return new static($game);
    }

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
        return (int) config('ai.agent_max_tokens', 2000);
    }

    public function instructions(): string
    {
        return 'You are a professional CS2 analyst with access to Faceit match data. '
            . 'You analyse matches fetched from the Faceit Data API including per-player '
            . 'stats (KDA, ADR, HS%, clutches), map scores, and half-time splits. '
            . 'Always be specific — cite player nicknames, exact scores, and stat numbers. '
            . 'Use the similarity search tool to find historical matches with similar '
            . 'patterns before making conclusions.' . "\n\n"
            . 'Output field rules:' . "\n"
            . '- headline: one punchy sentence (max 12 words) capturing the single most decisive moment or standout stat.' . "\n"
            . '- summary: 3-4 sentences. Cover (1) how the match unfolded across both halves, (2) which players had the biggest impact and why (cite their KDA/ADR), (3) the decisive turning point. Never write "null" or leave this empty.' . "\n"
            . '- key_moments: 3-5 specific round events, e.g. "Half 1 ended 9-3 as gynkiN dropped 8 kills on a single T-side push". Avoid generic statements like "X had clutches".' . "\n"
            . '- mvp: the single best-performing player nickname, based on KDA, ADR, and impact — not just kills.' . "\n"
            . '- economy_rating: 1-10 score for how well teams managed money (force buys, eco rounds, utility usage).' . "\n"
            . '- mechanical_rating: 1-10 score for the overall aim and mechanical skill on display.' . "\n"
            . '- score: 1-10 overall match quality (competitiveness, momentum swings, entertainment value).';
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
            'headline' => $schema->string()->required(),
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
