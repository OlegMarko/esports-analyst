<div wire:poll.60000ms>
    <div class="flex items-center gap-3 mb-6">
        <flux:heading size="xl">Live Matches</flux:heading>
        <span class="inline-flex items-center gap-1.5 text-xs text-zinc-500">
            <span class="size-2 rounded-full bg-green-500 animate-pulse"></span>
            Refreshes every 60s
        </span>
    </div>

    @if($this->matches->isEmpty())
        <div class="text-center text-zinc-500 py-20">
            <div class="text-4xl mb-3">📡</div>
            <p class="font-medium mb-1">No live matches detected</p>
            @if(!config('services.faceit.hub_ids'))
                <p class="text-sm mt-2 max-w-md mx-auto">
                    Add Faceit hub IDs to your <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded text-xs">.env</code>
                    to pull live matches from any hub:
                </p>
                <code class="inline-block mt-2 text-xs bg-zinc-100 dark:bg-zinc-800 px-3 py-1.5 rounded">
                    FACEIT_HUB_IDS=uuid1,uuid2
                </code>
                <p class="text-xs mt-2 text-zinc-400">
                    Hub ID is in the Faceit URL: faceit.com/en/hub/<strong>{hub-id}</strong>/overview
                </p>
            @else
                <p class="text-sm mt-1">Hubs are configured — no active matches right now. Refreshes every 60s.</p>
            @endif
        </div>
    @else
        <div class="grid gap-6">
            @foreach($this->matches as $lm)
                @php
                    $probA    = $lm->team_a_win_prob ?? 0.5;
                    $probB    = $lm->team_b_win_prob ?? 0.5;
                    $pctA     = round($probA * 100);
                    $pctB     = round($probB * 100);
                    $oddsA    = $probA > 0 ? round(1 / $probA, 2) : '–';
                    $oddsB    = $probB > 0 ? round(1 / $probB, 2) : '–';
                    $moe      = $lm->margin_of_error ?? 0;
                    $conf     = $lm->confidence ?? 0;
                    $confPct  = round($conf * 100);
                    $confColor = $conf >= 0.75 ? 'green' : ($conf >= 0.6 ? 'yellow' : 'zinc');
                @endphp

                <flux:card class="overflow-hidden">
                    {{-- Match header --}}
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-2">
                            @if($lm->status === 'ONGOING')
                                <flux:badge color="red" size="sm" icon="signal">LIVE</flux:badge>
                            @else
                                <flux:badge color="yellow" size="sm">{{ $lm->status }}</flux:badge>
                            @endif
                            @if($lm->map)
                                <flux:badge color="zinc" size="sm">{{ $lm->map }}</flux:badge>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:badge color="{{ $confColor }}" size="sm">
                                {{ $confPct }}% confidence
                            </flux:badge>
                            @if($lm->started_at)
                                <span class="text-xs text-zinc-400">{{ $lm->started_at->format('H:i') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Main prediction area --}}
                    <div class="grid grid-cols-[1fr_auto_1fr] gap-4 items-center mb-5">
                        {{-- Team A --}}
                        <div class="text-left">
                            <div class="text-lg font-bold truncate mb-1">{{ $lm->team_a_name }}</div>
                            @if($lm->team_a_elo_avg)
                                <div class="text-xs text-zinc-500 mb-3">avg ELO {{ number_format($lm->team_a_elo_avg) }}</div>
                            @endif
                            <div class="text-3xl font-black tabular-nums text-zinc-900 dark:text-white">{{ $pctA }}%</div>
                            <div class="text-sm text-zinc-500 mt-0.5">±{{ $moe }}%</div>
                            <div class="mt-2">
                                <div class="text-xs text-zinc-400 mb-0.5">Decimal odds</div>
                                <div class="text-xl font-bold text-blue-600 dark:text-blue-400 tabular-nums">{{ $oddsA }}</div>
                            </div>
                        </div>

                        {{-- VS divider --}}
                        <div class="text-center px-2">
                            <div class="text-sm font-bold text-zinc-400">VS</div>
                        </div>

                        {{-- Team B --}}
                        <div class="text-right">
                            <div class="text-lg font-bold truncate mb-1">{{ $lm->team_b_name }}</div>
                            @if($lm->team_b_elo_avg)
                                <div class="text-xs text-zinc-500 mb-3">avg ELO {{ number_format($lm->team_b_elo_avg) }}</div>
                            @endif
                            <div class="text-3xl font-black tabular-nums text-zinc-900 dark:text-white">{{ $pctB }}%</div>
                            <div class="text-sm text-zinc-500 mt-0.5">±{{ $moe }}%</div>
                            <div class="mt-2">
                                <div class="text-xs text-zinc-400 mb-0.5">Decimal odds</div>
                                <div class="text-xl font-bold text-blue-600 dark:text-blue-400 tabular-nums">{{ $oddsB }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Probability bar --}}
                    <div class="mb-5">
                        <div class="flex rounded-full overflow-hidden h-3">
                            <div
                                class="bg-blue-500 transition-all duration-500"
                                style="width: {{ $pctA }}%"
                            ></div>
                            <div class="flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
                        </div>
                        <div class="flex justify-between text-xs text-zinc-400 mt-1">
                            <span>{{ $lm->team_a_name }}</span>
                            <span>{{ $lm->team_b_name }}</span>
                        </div>
                    </div>

                    {{-- Rosters --}}
                    <div class="grid grid-cols-2 gap-4 border-t border-zinc-100 dark:border-zinc-700 pt-4">
                        @foreach(['a' => $lm->team_a_roster, 'b' => $lm->team_b_roster] as $side => $roster)
                            <div class="{{ $side === 'b' ? 'text-right' : '' }}">
                                <div class="text-xs text-zinc-400 mb-2 font-medium uppercase tracking-wide">
                                    {{ $side === 'a' ? $lm->team_a_name : $lm->team_b_name }}
                                </div>
                                <div class="space-y-1">
                                    @foreach((array) $roster as $player)
                                        <div class="flex items-center gap-2 {{ $side === 'b' ? 'flex-row-reverse' : '' }}">
                                            @if(!empty($player['skill_level']))
                                                <span class="text-xs font-medium px-1.5 py-0.5 rounded {{ $player['skill_level'] >= 8 ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' : ($player['skill_level'] >= 5 ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500') }}">
                                                    {{ $player['skill_level'] }}
                                                </span>
                                            @endif
                                            <a
                                                href="{{ route('player.show', $player['nickname'] ?? '') }}"
                                                wire:navigate
                                                class="text-sm hover:underline truncate"
                                            >{{ $player['nickname'] ?? '–' }}</a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Footer --}}
                    <div class="border-t border-zinc-100 dark:border-zinc-700 pt-3 mt-4 flex items-center justify-between text-xs text-zinc-400">
                        <span>{{ $lm->prediction_basis }}</span>
                        <span>Updated {{ $lm->fetched_at?->diffForHumans() }}</span>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif
</div>
