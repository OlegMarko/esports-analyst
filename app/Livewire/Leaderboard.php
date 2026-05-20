<?php

namespace App\Livewire;

use App\Services\LeaderboardService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Leaderboard extends Component
{
    public string $game = 'cs2';
    public string $metric = 'kda';

    #[Computed]
    public function entries(): array
    {
        $raw = app(LeaderboardService::class)->top($this->game, $this->metric, 25);

        if (empty($raw)) {
            return [];
        }

        return collect($raw)
            ->map(fn ($score, $nickname) => [
                'name'  => $nickname,
                'score' => (float) $score,
            ])
            ->values()
            ->toArray();
    }

    public function metricLabel(): string
    {
        return match ($this->metric) {
            'kda' => 'KDA',
            'frags' => 'Kills',
            'adr' => 'ADR',
            'clutches_won' => 'Clutches',
            default => strtoupper($this->metric),
        };
    }

    public function render()
    {
        return view('livewire.leaderboard');
    }
}
