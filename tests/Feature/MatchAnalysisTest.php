<?php

use App\Ai\Agents\MatchAnalystAgent;
use App\Jobs\GenerateSummaryJob;
use App\Jobs\UpdateLeaderboardsJob;
use App\Models\GameMatch;
use App\Models\Player;
use Laravel\Ai\Ai;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Reranking;

beforeEach(function () {
    Embeddings::fake();
    Reranking::fake();
});

it('generates and persists ai_summary on the match', function () {
    Ai::fakeAgent(MatchAnalystAgent::class);

    $match = GameMatch::factory()
        ->has(Player::factory()->count(10), 'players')
        ->create();

    app()->call([new GenerateSummaryJob($match->id), 'handle']);

    expect($match->fresh()->ai_summary)->not->toBeEmpty();
});

it('records that the analyst agent was prompted', function () {
    Ai::fakeAgent(MatchAnalystAgent::class);

    $match = GameMatch::factory()
        ->has(Player::factory()->count(10), 'players')
        ->create();

    app()->call([new GenerateSummaryJob($match->id), 'handle']);

    $mapName = $match->getAttribute('map');
    Ai::assertAgentWasPrompted(MatchAnalystAgent::class, fn ($prompt) => str_contains($prompt->prompt, $mapName));
});

it('dispatches UpdateLeaderboardsJob after generating summary', function () {
    Ai::fakeAgent(MatchAnalystAgent::class);
    Queue::fake();

    $match = GameMatch::factory()
        ->has(Player::factory()->count(10), 'players')
        ->create();

    app()->call([new GenerateSummaryJob($match->id), 'handle']);

    Queue::assertPushed(UpdateLeaderboardsJob::class, fn ($job) => $job->matchId === $match->id);
});

it('sets summary_at timestamp when summary is generated', function () {
    Ai::fakeAgent(MatchAnalystAgent::class);

    $match = GameMatch::factory()
        ->has(Player::factory()->count(10), 'players')
        ->create(['summary_at' => null]);

    app()->call([new GenerateSummaryJob($match->id), 'handle']);

    expect($match->fresh()->summary_at)->not->toBeNull();
});

it('throws when agent is prompted without a configured fake response', function () {
    Ai::fakeAgent(MatchAnalystAgent::class)->preventStrayPrompts();

    $match = GameMatch::factory()
        ->has(Player::factory()->count(10), 'players')
        ->create();

    expect(fn () => app()->call([new GenerateSummaryJob($match->id), 'handle']))
        ->toThrow(RuntimeException::class);
});
