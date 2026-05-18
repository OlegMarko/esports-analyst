<?php

use App\Models\GameMatch;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?int $matchId = null;

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
    }
};
?>

<div>
    @if(! $this->match)
        <div class="flex flex-col items-center justify-center h-64 text-zinc-500 gap-3">
            <flux:icon.cursor-arrow-rays class="size-10 opacity-30" />
            <span>Select a match to see analysis</span>
        </div>
    @else
        @php $match = $this->match; @endphp

        {{-- Match header --}}
        <flux:card class="mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <flux:badge color="zinc" size="lg">{{ $match->map }}</flux:badge>
                <div class="flex-1 min-w-0">
                    <div class="text-xl font-bold">
                        {{ $match->team_a_name }}
                        <span class="font-mono px-2">{{ $match->team_a_score }} – {{ $match->team_b_score }}</span>
                        {{ $match->team_b_name }}
                    </div>
                    <div class="text-sm text-zinc-500 mt-0.5">
                        {{ $match->played_at?->format('M d, Y · H:i') }}
                        @if($match->duration_minutes)
                            · {{ $match->duration_minutes }} min
                        @endif
                        @if($match->first_half_score && $match->second_half_score)
                            · Halves: {{ $match->first_half_score }} / {{ $match->second_half_score }}
                        @endif
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Player stats by team --}}
        <div class="grid gap-4 lg:grid-cols-2 mb-6">
            @foreach(['a' => $match->team_a_name, 'b' => $match->team_b_name] as $side => $teamName)
                <flux:card>
                    <flux:heading class="mb-3 truncate">{{ $teamName }}</flux:heading>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Player</flux:table.column>
                            <flux:table.column>K/D/A</flux:table.column>
                            <flux:table.column>ADR</flux:table.column>
                            <flux:table.column>HS%</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($match->players->where('team', $side)->sortByDesc('kills') as $player)
                                <flux:table.row :key="$player->id">
                                    <flux:table.cell variant="strong">{{ $player->faceit_nickname }}</flux:table.cell>
                                    <flux:table.cell>{{ $player->kda_label }}</flux:table.cell>
                                    <flux:table.cell>{{ $player->adr }}</flux:table.cell>
                                    <flux:table.cell>{{ $player->hs_percent }}%</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:card>
            @endforeach
        </div>

        {{-- AI Analysis panel — polls until summary is available --}}
        <div @unless($match->ai_summary) wire:poll.5000ms @endunless>
            <flux:card>
                <div class="flex items-center gap-2 mb-4">
                    <flux:icon.sparkles class="text-yellow-500 size-5" />
                    <flux:heading>AI Analysis</flux:heading>
                </div>

                @if($match->ai_summary)
                    <flux:text class="whitespace-pre-line leading-relaxed">{{ $match->ai_summary }}</flux:text>
                @else
                    <div class="flex items-center gap-3 text-zinc-500 py-4">
                        <flux:icon.arrow-path class="size-5 animate-spin" />
                        <span>Generating analysis… this may take a minute.</span>
                    </div>
                @endif
            </flux:card>
        </div>
    @endif
</div>
