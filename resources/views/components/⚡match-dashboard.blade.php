<?php

use App\Models\GameMatch;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $mapFilter = '';
    public ?int $selectedMatchId = null;

    #[Computed]
    public function matches(): \Illuminate\Database\Eloquent\Collection
    {
        return GameMatch::query()
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

    public function selectMatch(int $id): void
    {
        $this->selectedMatchId = $id;
        $this->dispatch('match-selected', matchId: $id);
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Recent Matches</flux:heading>

        @if(!empty($this->maps))
            <flux:select wire:model.live="mapFilter" placeholder="All maps" class="w-48">
                @foreach($this->maps as $map)
                    <flux:select.option value="{{ $map }}">{{ $map }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
    </div>

    @if($this->matches->isEmpty())
        <div class="text-center text-zinc-500 py-16">
            No matches yet. The scheduler polls Faceit every 10 minutes automatically.
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($this->matches as $match)
                <flux:card
                    wire:click="selectMatch({{ $match->id }})"
                    class="cursor-pointer transition-all hover:shadow-md {{ $selectedMatchId === $match->id ? 'ring-2 ring-blue-500 dark:ring-blue-400' : '' }}"
                >
                    <div class="flex items-center justify-between mb-3">
                        <flux:badge color="zinc" size="sm">{{ $match->map }}</flux:badge>
                        @if($match->ai_summary)
                            <flux:badge color="green" size="sm" icon="sparkles">AI Ready</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Pending</flux:badge>
                        @endif
                    </div>

                    <div class="mb-2">
                        <div class="text-sm text-zinc-500 truncate">{{ $match->team_a_name }}</div>
                        <div class="text-2xl font-bold tracking-tight font-mono">
                            {{ $match->team_a_score }}
                            <span class="text-zinc-400 text-lg">–</span>
                            {{ $match->team_b_score }}
                        </div>
                        <div class="text-sm text-zinc-500 truncate">{{ $match->team_b_name }}</div>
                    </div>

                    <div class="text-xs text-zinc-400 mt-3">
                        {{ $match->played_at?->format('M d, Y') }}
                        @if($match->duration_minutes)
                            · {{ $match->duration_minutes }}m
                        @endif
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif
</div>
