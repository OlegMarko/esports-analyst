<?php

use App\Services\FaceitNormalizer;

beforeEach(function () {
    $this->normalizer = new FaceitNormalizer();
});

it('parses team scores from round stats', function () {
    $details = faceitMatchDetailsFixture();
    $stats = faceitMatchStatsFixture(teamAScore: 16, teamBScore: 14);

    $payload = $this->normalizer->toMatchPayload($details, $stats);

    expect($payload['team_a_score'])->toBe(16)
        ->and($payload['team_b_score'])->toBe(14);
});

it('sets outcome to team_a when team A wins', function () {
    $details = faceitMatchDetailsFixture();
    $stats = faceitMatchStatsFixture(teamAScore: 16, teamBScore: 10);

    $payload = $this->normalizer->toMatchPayload($details, $stats);

    expect($payload['outcome'])->toBe('team_a');
});

it('sets outcome to team_b when team B wins', function () {
    $details = faceitMatchDetailsFixture();
    $stats = faceitMatchStatsFixture(teamAScore: 8, teamBScore: 16);

    $payload = $this->normalizer->toMatchPayload($details, $stats);

    expect($payload['outcome'])->toBe('team_b');
});

it('calculates duration in minutes from timestamps', function () {
    $details = faceitMatchDetailsFixture();
    // started_at=1747000000, finished_at=1747003600 → 60 minutes
    $stats = faceitMatchStatsFixture();

    $payload = $this->normalizer->toMatchPayload($details, $stats);

    expect($payload['duration_minutes'])->toBe(60);
});

it('maps faceit_match_id from details', function () {
    $details = faceitMatchDetailsFixture('my-match-id-001');
    $stats = faceitMatchStatsFixture();

    $payload = $this->normalizer->toMatchPayload($details, $stats);

    expect($payload['faceit_match_id'])->toBe('my-match-id-001');
});

it('maps team names from factions', function () {
    $details = faceitMatchDetailsFixture();
    $stats = faceitMatchStatsFixture();

    $payload = $this->normalizer->toMatchPayload($details, $stats);

    expect($payload['team_a_name'])->toBe('Alpha Squad')
        ->and($payload['team_b_name'])->toBe('Bravo Force');
});

it('maps map from round stats', function () {
    $details = faceitMatchDetailsFixture();
    $stats = faceitMatchStatsFixture(map: 'de_inferno');

    $payload = $this->normalizer->toMatchPayload($details, $stats);

    expect($payload['map'])->toBe('de_inferno');
});

it('returns 10 player payloads (5 per team)', function () {
    $details = faceitMatchDetailsFixture();
    $stats = faceitMatchStatsFixture();

    $players = $this->normalizer->toPlayerPayloads($stats, $details);

    expect($players)->toHaveCount(10);
});

it('assigns correct team letters to players', function () {
    $details = faceitMatchDetailsFixture();
    $stats = faceitMatchStatsFixture();

    $players = $this->normalizer->toPlayerPayloads($stats, $details);

    $teamA = array_filter($players, fn ($p) => $p['team'] === 'a');
    $teamB = array_filter($players, fn ($p) => $p['team'] === 'b');

    expect($teamA)->toHaveCount(5)
        ->and($teamB)->toHaveCount(5);
});

it('sets result to win for players on the winning team', function () {
    $details = faceitMatchDetailsFixture();
    $stats = faceitMatchStatsFixture(teamAScore: 16, teamBScore: 10);

    $players = $this->normalizer->toPlayerPayloads($stats, $details);

    $teamAResults = array_unique(array_column(
        array_filter($players, fn ($p) => $p['team'] === 'a'),
        'result'
    ));
    $teamBResults = array_unique(array_column(
        array_filter($players, fn ($p) => $p['team'] === 'b'),
        'result'
    ));

    expect($teamAResults)->toBe(['win'])
        ->and($teamBResults)->toBe(['loss']);
});

it('maps player stats including kills deaths assists and adr', function () {
    $details = faceitMatchDetailsFixture();
    $stats = faceitMatchStatsFixture();

    $players = $this->normalizer->toPlayerPayloads($stats, $details);

    foreach ($players as $player) {
        expect($player['kills'])->toBeInt()->toBeGreaterThan(0)
            ->and($player['deaths'])->toBeInt()->toBeGreaterThan(0)
            ->and($player['assists'])->toBeInt()
            ->and($player['adr'])->toBeFloat();
    }
});
