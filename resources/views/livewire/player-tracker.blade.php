<div>
    <flux:heading size="xl" class="mb-6">Tracked Players</flux:heading>

    <flux:card class="mb-6">
        <form wire:submit="addPlayer" class="flex gap-3 items-start">
            <div class="flex-1">
                <flux:input
                    wire:model="nickname"
                    placeholder="Faceit nickname"
                    :invalid="$errors->has('nickname')"
                />
                <flux:error name="nickname" class="mt-1" />
                @if($error)
                    <p class="text-sm text-red-600 mt-1">{{ $error }}</p>
                @endif
            </div>
            <flux:button type="submit" variant="primary">Track</flux:button>
        </form>
    </flux:card>

    @if($this->players->isEmpty())
        <div class="text-center text-zinc-500 py-16">
            No players tracked yet. Add a Faceit nickname above to get started.
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Player</flux:table.column>
                <flux:table.column>Level</flux:table.column>
                <flux:table.column>ELO</flux:table.column>
                <flux:table.column>Last Polled</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->players as $player)
                <flux:table.row :key="$player->id">
                    <flux:table.cell variant="strong">
                        <div class="flex items-center gap-3">
                            @if($player->avatar)
                                <img src="{{ $player->avatar }}" alt="{{ $player->faceit_nickname }}" class="size-8 rounded-full" />
                            @else
                                <div class="size-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold">
                                    {{ strtoupper(substr($player->faceit_nickname, 0, 2)) }}
                                </div>
                            @endif
                            <a href="{{ route('player.show', $player->faceit_nickname) }}" wire:navigate class="hover:underline">
                                {{ $player->faceit_nickname }}
                            </a>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $player->faceit_level >= 8 ? 'yellow' : ($player->faceit_level >= 5 ? 'blue' : 'zinc') }}">
                            {{ $player->faceit_level }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ number_format($player->elo) }}</flux:table.cell>
                    <flux:table.cell>{{ $player->last_polled_at?->diffForHumans() ?? 'Never' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button
                            wire:click="removePlayer({{ $player->id }})"
                            wire:confirm="Remove {{ $player->faceit_nickname }} from tracking?"
                            variant="ghost"
                            size="sm"
                            icon="trash"
                        />
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
