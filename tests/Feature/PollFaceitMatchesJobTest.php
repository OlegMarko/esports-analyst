<?php

use App\Jobs\IngestMatchJob;
use App\Jobs\PollFaceitMatchesJob;
use App\Models\TrackedPlayer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('dispatches IngestMatchJob for each new match', function () {
    Queue::fake();
    Redis::shouldReceive('exists')->andReturn(0);
    Redis::shouldReceive('setex');

    TrackedPlayer::factory()->create(['active' => true]);

    Http::fake([
        '*/players/*/history*' => Http::response([
            'items' => [
                ['match_id' => 'match-new-001'],
                ['match_id' => 'match-new-002'],
            ],
        ], 200),
    ]);

    app()->call([new PollFaceitMatchesJob(), 'handle']);

    Queue::assertPushed(IngestMatchJob::class, 2);
});

it('skips matches already tracked in Redis', function () {
    Queue::fake();
    Redis::shouldReceive('exists')->andReturn(1);

    TrackedPlayer::factory()->create(['active' => true]);

    Http::fake([
        '*/players/*/history*' => Http::response([
            'items' => [['match_id' => 'match-old-001']],
        ], 200),
    ]);

    app()->call([new PollFaceitMatchesJob(), 'handle']);

    Queue::assertNothingPushed();
});

it('updates last_polled_at after successful poll', function () {
    Queue::fake();
    Redis::shouldReceive('exists')->andReturn(0);
    Redis::shouldReceive('setex');

    $player = TrackedPlayer::factory()->create(['active' => true, 'last_polled_at' => null]);

    Http::fake([
        '*/players/*/history*' => Http::response(['items' => []], 200),
    ]);

    app()->call([new PollFaceitMatchesJob(), 'handle']);

    expect($player->fresh()->last_polled_at)->not->toBeNull();
});

it('skips inactive players', function () {
    Queue::fake();

    TrackedPlayer::factory()->create(['active' => false]);

    Http::fake([
        '*/players/*/history*' => Http::response(['items' => [['match_id' => 'match-x']]], 200),
    ]);

    app()->call([new PollFaceitMatchesJob(), 'handle']);

    Queue::assertNothingPushed();
    Http::assertNothingSent();
});

it('continues processing other players after a single failure', function () {
    Queue::fake();
    Redis::shouldReceive('exists')->andReturn(0);
    Redis::shouldReceive('setex');

    TrackedPlayer::factory()->create(['active' => true, 'faceit_id' => 'id-fail']);
    TrackedPlayer::factory()->create(['active' => true, 'faceit_id' => 'id-ok']);

    Http::fake([
        '*/players/id-fail/history*' => Http::response([], 500),
        '*/players/id-ok/history*' => Http::response([
            'items' => [['match_id' => 'match-ok-001']],
        ], 200),
    ]);

    app()->call([new PollFaceitMatchesJob(), 'handle']);

    Queue::assertPushed(IngestMatchJob::class, fn ($job) => $job->faceitMatchId === 'match-ok-001');
});
