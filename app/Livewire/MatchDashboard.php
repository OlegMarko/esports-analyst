<?php

namespace App\Livewire;

use App\Models\GameMatch;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MatchDashboard extends Component
{
    public string $mapFilter = '';
    public ?int $selectedMatchId = null;

    #[Computed]
    public function matches(): \Illuminate\Database\Eloquent\Collection
    {
        return GameMatch::query()
            ->with('players')
            ->when($this->mapFilter, fn ($q) => $q->where('map', $this->mapFilter))
            ->orderByDesc('played_at')
            ->limit(30)
            ->get();
    }

    #[Computed]
    public function maps(): array
    {
        return GameMatch::query()
            ->distinct()
            ->orderBy('map')
            ->pluck('map')
            ->toArray();
    }

    #[Computed]
    public function selectedMatch(): ?GameMatch
    {
        return $this->selectedMatchId
            ? GameMatch::with('players')->find($this->selectedMatchId)
            : null;
    }

    public function selectMatch(int $id): void
    {
        $this->selectedMatchId = $id;
        Flux::modal('match-detail')->show();
    }

    public function render()
    {
        return view('livewire.match-dashboard');
    }
}
