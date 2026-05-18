<?php

use App\Models\TrackedPlayer;
use App\Services\LeaderboardService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
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

        $nicknames = TrackedPlayer::whereIn('faceit_id', array_keys($raw))
            ->pluck('faceit_nickname', 'faceit_id')
            ->toArray();

        return collect($raw)
            ->map(fn ($score, $id) => [
                'id' => $id,
                'name' => $nicknames[$id] ?? $id,
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
};
?>

<div>
    <flux:heading size="xl" class="mb-6">Leaderboards</flux:heading>

    {{-- Metric tabs --}}
    <div class="flex gap-1 mb-6 border-b border-zinc-200 dark:border-zinc-700">
        @foreach(['kda' => 'KDA', 'frags' => 'Kills', 'adr' => 'ADR', 'clutches_won' => 'Clutches'] as $key => $label)
            <button
                wire:click="$set('metric', '{{ $key }}')"
                class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px
                    {{ $metric === $key
                        ? 'border-zinc-800 text-zinc-900 dark:border-white dark:text-white'
                        : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if(empty($this->entries))
        <div class="text-center text-zinc-500 py-16">
            No leaderboard data yet. Stats are recorded after match summaries are generated.
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-16">#</flux:table.column>
                <flux:table.column>Player</flux:table.column>
                <flux:table.column>{{ $this->metricLabel() }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->entries as $entry)
                    <flux:table.row :key="$entry['id']">
                        <flux:table.cell>
                            @if($loop->index < 3)
                                <span>{{ ['🥇', '🥈', '🥉'][$loop->index] }}</span>
                            @else
                                <span class="text-zinc-400">{{ $loop->iteration }}</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell variant="strong">{{ $entry['name'] }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($entry['score'], 2) }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
