<?php

namespace App\Livewire;

use App\Models\GameMatch;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class MatchAnalysis extends Component
{
    public ?int $matchId = null;
    public bool $analysisReady = false;

    public function mount(?int $matchId = null): void
    {
        $this->matchId = $matchId;
        $this->checkAnalysis();
    }

    #[Computed]
    public function match(): ?GameMatch
    {
        if (! $this->matchId) {
            return null;
        }

        return GameMatch::with('players')->find($this->matchId);
    }

    #[On('match-selected')]
    public function loadMatch(int $matchId): void
    {
        $this->matchId = $matchId;
        $this->checkAnalysis();
    }

    public function checkAnalysis(): void
    {
        if (! $this->matchId) {
            return;
        }

        $match = GameMatch::find($this->matchId);
        $this->analysisReady = $match !== null && ! empty($match->ai_summary);
    }

    public function render()
    {
        return view('livewire.match-analysis');
    }
}
