<?php

use App\Livewire\PlayerTracker;
use App\Models\TrackedPlayer;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('renders the player tracker component', function () {
    Livewire::test(PlayerTracker::class)
        ->assertSee('Tracked Players')
        ->assertSee('Faceit nickname');
});

it('shows a message when no players are tracked', function () {
    Livewire::test(PlayerTracker::class)
        ->assertSee('No players tracked yet');
});

it('shows tracked players in the list', function () {
    TrackedPlayer::factory()->create(['faceit_nickname' => 's1mpleGOAT']);

    Livewire::test(PlayerTracker::class)
        ->assertSee('s1mpleGOAT');
});

it('adds a player when a valid nickname is submitted', function () {
    Http::fake([
        '*/players*' => Http::response([
            'player_id' => 'uuid-faceit-001',
            'nickname' => 'NaViS1mple',
            'steam_id_64' => '76561198034202275',
            'avatar' => null,
            'games' => [
                'cs2' => ['skill_level' => 10, 'faceit_elo' => 3500],
            ],
        ], 200),
    ]);

    Livewire::test(PlayerTracker::class)
        ->set('nickname', 'NaViS1mple')
        ->call('addPlayer')
        ->assertHasNoErrors();

    expect(TrackedPlayer::where('faceit_nickname', 'NaViS1mple')->exists())->toBeTrue();
});

it('shows an error when the player is not found on Faceit', function () {
    Http::fake([
        '*/players*' => Http::response(['message' => 'Not Found'], 404),
    ]);

    Livewire::test(PlayerTracker::class)
        ->set('nickname', 'does-not-exist-xyz')
        ->call('addPlayer')
        ->assertSet('error', 'Player not found on Faceit');
});

it('shows a validation error when nickname is empty', function () {
    Livewire::test(PlayerTracker::class)
        ->set('nickname', '')
        ->call('addPlayer')
        ->assertHasErrors(['nickname' => 'required']);
});

it('removes a player from the list', function () {
    $player = TrackedPlayer::factory()->create();

    Livewire::test(PlayerTracker::class)
        ->call('removePlayer', $player->id);

    expect(TrackedPlayer::find($player->id))->toBeNull();
});
