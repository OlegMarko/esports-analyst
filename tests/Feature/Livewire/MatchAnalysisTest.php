<?php

use App\Livewire\MatchAnalysis;
use App\Models\GameMatch;
use App\Models\Player;
use Livewire\Livewire;

it('shows the select-a-match placeholder by default', function () {
    Livewire::test(MatchAnalysis::class)
        ->assertSee('Select a match to see analysis');
});

it('shows match details when a match id is mounted', function () {
    $match = GameMatch::factory()
        ->has(Player::factory()->count(10), 'players')
        ->create([
            'map' => 'de_mirage',
            'team_a_name' => 'Alpha Squad',
            'team_b_name' => 'Bravo Force',
            'team_a_score' => 16,
            'team_b_score' => 14,
        ]);

    Livewire::test(MatchAnalysis::class, ['matchId' => $match->id])
        ->assertSee('de_mirage')
        ->assertSee('Alpha Squad')
        ->assertSee('Bravo Force')
        ->assertSee('16')
        ->assertSee('14');
});

it('shows player nicknames from both teams', function () {
    $match = GameMatch::factory()->create();
    Player::factory()->create(['match_id' => $match->id, 'team' => 'a', 'faceit_nickname' => 'TopFraggerA']);
    Player::factory()->create(['match_id' => $match->id, 'team' => 'b', 'faceit_nickname' => 'TopFraggerB']);

    Livewire::test(MatchAnalysis::class, ['matchId' => $match->id])
        ->assertSee('TopFraggerA')
        ->assertSee('TopFraggerB');
});

it('responds to the match-selected event', function () {
    $match = GameMatch::factory()
        ->has(Player::factory()->count(4), 'players')
        ->create(['map' => 'de_nuke']);

    Livewire::test(MatchAnalysis::class)
        ->dispatch('match-selected', matchId: $match->id)
        ->assertSee('de_nuke');
});

it('shows ai summary when analysis is ready', function () {
    $match = GameMatch::factory()
        ->has(Player::factory()->count(4), 'players')
        ->create(['ai_summary' => 'Great performance by all players. Alpha dominated.']);

    Livewire::test(MatchAnalysis::class, ['matchId' => $match->id])
        ->assertSet('analysisReady', true)
        ->assertSee('Great performance by all players');
});

it('shows generating message when analysis is not ready', function () {
    $match = GameMatch::factory()
        ->has(Player::factory()->count(4), 'players')
        ->create(['ai_summary' => null]);

    Livewire::test(MatchAnalysis::class, ['matchId' => $match->id])
        ->assertSet('analysisReady', false)
        ->assertSee('Generating AI analysis');
});

it('sets analysisReady to true after checkAnalysis when summary is written', function () {
    $match = GameMatch::factory()
        ->has(Player::factory()->count(4), 'players')
        ->create(['ai_summary' => null]);

    $component = Livewire::test(MatchAnalysis::class, ['matchId' => $match->id])
        ->assertSet('analysisReady', false);

    $match->update(['ai_summary' => 'Now the summary is here.']);

    $component->call('checkAnalysis')
        ->assertSet('analysisReady', true);
});
