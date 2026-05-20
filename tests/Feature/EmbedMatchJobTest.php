<?php

use App\Jobs\EmbedMatchJob;
use App\Models\GameMatch;
use App\Models\MatchAspectEmbedding;
use App\Models\Player;
use Laravel\Ai\Embeddings;

beforeEach(function () {
    Embeddings::fake();
});

it('creates one embedding row per aspect for a match', function () {
    $match = GameMatch::factory()
        ->has(Player::factory()->count(10), 'players')
        ->create();

    app()->call([new EmbedMatchJob($match->id), 'handle']);

    expect(MatchAspectEmbedding::where('match_id', $match->id)->count())->toBe(4);
});

it('stores all four aspects', function () {
    $match = GameMatch::factory()
        ->has(Player::factory()->count(10), 'players')
        ->create();

    app()->call([new EmbedMatchJob($match->id), 'handle']);

    $aspects = MatchAspectEmbedding::where('match_id', $match->id)
        ->pluck('aspect')
        ->sort()
        ->values()
        ->toArray();

    expect($aspects)->toBe(['economy', 'mechanics', 'momentum', 'teamplay']);
});

it('records that embeddings were generated', function () {
    $match = GameMatch::factory()
        ->has(Player::factory()->count(10), 'players')
        ->create();

    app()->call([new EmbedMatchJob($match->id), 'handle']);

    Embeddings::assertGenerated(fn ($prompt) => count($prompt->inputs) === 1);
});

it('updates existing embedding rows on re-run', function () {
    $match = GameMatch::factory()
        ->has(Player::factory()->count(10), 'players')
        ->create();

    app()->call([new EmbedMatchJob($match->id), 'handle']);
    app()->call([new EmbedMatchJob($match->id), 'handle']);

    expect(MatchAspectEmbedding::where('match_id', $match->id)->count())->toBe(4);
});

it('stores aspect_score between 0 and 1', function () {
    $match = GameMatch::factory()
        ->has(Player::factory()->count(10), 'players')
        ->create();

    app()->call([new EmbedMatchJob($match->id), 'handle']);

    MatchAspectEmbedding::where('match_id', $match->id)->each(function ($embedding) {
        expect($embedding->aspect_score)->toBeGreaterThanOrEqual(0.0)
            ->toBeLessThanOrEqual(1.0);
    });
});
