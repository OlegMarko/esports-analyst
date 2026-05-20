<?php

namespace App\Livewire;

use App\Models\LiveMatch;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LiveMatches extends Component
{
    #[Computed]
    public function matches(): \Illuminate\Database\Eloquent\Collection
    {
        return LiveMatch::orderByDesc('started_at')->get();
    }

    public function render()
    {
        return view('livewire.live-matches');
    }
}
