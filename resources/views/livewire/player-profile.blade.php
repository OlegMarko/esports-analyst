<div>
    {{-- Header --}}
    <div class="flex items-center gap-5 mb-8">
        @if($this->trackedPlayer?->avatar)
            <img src="{{ $this->trackedPlayer->avatar }}" alt="{{ $this->nickname }}" class="size-16 rounded-full ring-2 ring-zinc-200 dark:ring-zinc-700" />
        @else
            <div class="size-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-2xl font-bold">
                {{ strtoupper(substr($this->nickname, 0, 2)) }}
            </div>
        @endif

        <div>
            <flux:heading size="xl">{{ $this->nickname }}</flux:heading>
            @if($this->trackedPlayer)
                <div class="flex items-center gap-3 mt-1">
                    <flux:badge color="{{ $this->trackedPlayer->faceit_level >= 8 ? 'yellow' : ($this->trackedPlayer->faceit_level >= 5 ? 'blue' : 'zinc') }}">
                        Level {{ $this->trackedPlayer->faceit_level }}
                    </flux:badge>
                    <span class="text-zinc-500 text-sm">{{ number_format($this->trackedPlayer->elo) }} ELO</span>
                </div>
            @endif
        </div>

        <flux:spacer />

        <div class="flex items-center gap-2">
            @if($this->trackedPlayer)
                <flux:badge color="green" icon="check">Tracked</flux:badge>
            @else
                <flux:button wire:click="trackPlayer" variant="primary" size="sm" icon="plus">
                    Track Player
                </flux:button>
            @endif

            <flux:button :href="'https://www.faceit.com/en/players/' . $this->nickname" target="_blank" variant="ghost" size="sm" icon="arrow-top-right-on-square">
                Faceit Profile
            </flux:button>
        </div>
    </div>

    @if(empty($this->stats))
        <div class="text-center text-zinc-500 py-16">No match data found for this player.</div>
    @else
        {{-- Stat cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <flux:card class="text-center">
                <div class="text-2xl font-bold">{{ $this->stats['matches'] }}</div>
                <div class="text-sm text-zinc-500 mt-1">Matches</div>
            </flux:card>
            <flux:card class="text-center">
                <div class="text-2xl font-bold">{{ $this->stats['win_rate'] }}%</div>
                <div class="text-sm text-zinc-500 mt-1">Win Rate</div>
            </flux:card>
            <flux:card class="text-center">
                <div class="text-2xl font-bold">{{ $this->stats['avg_kda'] }}</div>
                <div class="text-sm text-zinc-500 mt-1">Avg KDA</div>
            </flux:card>
            <flux:card class="text-center">
                <div class="text-2xl font-bold">{{ $this->stats['avg_adr'] }}</div>
                <div class="text-sm text-zinc-500 mt-1">Avg ADR</div>
            </flux:card>
            <flux:card class="text-center">
                <div class="text-2xl font-bold">{{ $this->stats['avg_kills'] }}</div>
                <div class="text-sm text-zinc-500 mt-1">Avg Kills</div>
            </flux:card>
            <flux:card class="text-center">
                <div class="text-2xl font-bold">{{ $this->stats['avg_hs'] }}%</div>
                <div class="text-sm text-zinc-500 mt-1">Avg HS%</div>
            </flux:card>
            <flux:card class="text-center">
                <div class="text-2xl font-bold">{{ number_format($this->stats['total_kills']) }}</div>
                <div class="text-sm text-zinc-500 mt-1">Total Kills</div>
            </flux:card>
            <flux:card class="text-center">
                <div class="text-2xl font-bold">{{ $this->stats['wins'] }}</div>
                <div class="text-sm text-zinc-500 mt-1">Wins</div>
            </flux:card>
        </div>

        {{-- Performance brief --}}
        @if($this->trackedPlayer)
            <div class="mb-8 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <flux:icon.sparkles class="text-yellow-500 size-4" />
                        <span class="text-sm font-medium">AI Performance Brief</span>
                    </div>
                    <flux:button wire:click="regenerateBrief" variant="ghost" size="xs" icon="arrow-path">
                        Regenerate
                    </flux:button>
                </div>
                @if($this->trackedPlayer->performance_brief)
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ $this->trackedPlayer->performance_brief }}</p>
                @else
                    <p class="text-sm text-zinc-400 italic">No brief yet — will be generated after the next match is processed, or click Regenerate.</p>
                @endif
            </div>
        @endif

        {{-- Recent matches --}}
        <flux:heading class="mb-4">Recent Matches</flux:heading>

        @if($this->recentMatches->isEmpty())
            <div class="text-center text-zinc-500 py-8">No matches yet.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>Map</flux:table.column>
                    <flux:table.column>Score</flux:table.column>
                    <flux:table.column>K/D/A</flux:table.column>
                    <flux:table.column>KDA</flux:table.column>
                    <flux:table.column>ADR</flux:table.column>
                    <flux:table.column>HS%</flux:table.column>
                    <flux:table.column>Result</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->recentMatches as $player)
                        <flux:table.row
                            :key="$player->id"
                            wire:click="openMatch({{ $player->match_id }})"
                            class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                        >
                            <flux:table.cell class="text-zinc-500 text-sm">
                                {{ $player->match?->played_at?->format('M d, Y') ?? '–' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="zinc" size="sm">{{ $player->match?->map ?? '–' }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-sm">
                                {{ $player->match?->team_a_score }} – {{ $player->match?->team_b_score }}
                            </flux:table.cell>
                            <flux:table.cell>{{ $player->kda_label }}</flux:table.cell>
                            <flux:table.cell>{{ $player->kda }}</flux:table.cell>
                            <flux:table.cell>{{ $player->adr }}</flux:table.cell>
                            <flux:table.cell>{{ $player->hs_percent }}%</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $player->result === 'win' ? 'green' : 'red' }}" size="sm">
                                    {{ ucfirst($player->result) }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    @endif

    {{-- Match detail modal --}}
    <flux:modal name="match-detail" class="w-full max-w-3xl">
        @if($this->selectedMatch)
            @php $match = $this->selectedMatch; @endphp

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <flux:badge color="zinc">{{ $match->map }}</flux:badge>
                        <span class="text-zinc-500 text-sm">{{ $match->played_at?->format('M d, Y · H:i') }}</span>
                        @if($match->duration_minutes)
                            <span class="text-zinc-500 text-sm">· {{ $match->duration_minutes }}min</span>
                        @endif
                    </div>
                    <div class="text-xl font-bold">
                        {{ $match->team_a_name }}
                        <span class="font-mono px-2 text-zinc-400">{{ $match->team_a_score }} – {{ $match->team_b_score }}</span>
                        {{ $match->team_b_name }}
                    </div>
                    @if($match->first_half_score && $match->second_half_score)
                        <div class="text-sm text-zinc-500 mt-0.5">Halves: {{ $match->first_half_score }} / {{ $match->second_half_score }}</div>
                    @endif
                </div>
            </div>

            {{-- Player stats by team --}}
            <div class="grid gap-4 lg:grid-cols-2 mb-6">
                @foreach(['a' => $match->team_a_name, 'b' => $match->team_b_name] as $side => $teamName)
                    <div>
                        <div class="text-sm font-semibold text-zinc-500 mb-2 truncate">{{ $teamName }}</div>
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Player</flux:table.column>
                                <flux:table.column>K/D/A</flux:table.column>
                                <flux:table.column>KDA</flux:table.column>
                                <flux:table.column>ADR</flux:table.column>
                                <flux:table.column>HS%</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach($match->players->where('team', $side)->sortByDesc('kills') as $p)
                                    <flux:table.row
                                        :key="$p->id"
                                        @class(['font-semibold bg-zinc-50 dark:bg-zinc-700/30' => $p->faceit_nickname === $nickname])
                                    >
                                        <flux:table.cell variant="strong">
                                            <div class="flex items-center gap-1">
                                                @if($p->faceit_nickname === $nickname)
                                                    <span class="size-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                                @endif
                                                {{ $p->faceit_nickname }}
                                            </div>
                                        </flux:table.cell>
                                        <flux:table.cell>{{ $p->kda_label }}</flux:table.cell>
                                        <flux:table.cell>{{ $p->kda }}</flux:table.cell>
                                        <flux:table.cell>{{ $p->adr }}</flux:table.cell>
                                        <flux:table.cell>{{ $p->hs_percent }}%</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                @endforeach
            </div>

            {{-- AI Analysis --}}
            @if($match->ai_summary)
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 space-y-4">
                    <div class="flex items-center gap-2">
                        <flux:icon.sparkles class="text-yellow-500 size-4" />
                        <span class="text-sm font-medium">AI Analysis</span>
                    </div>

                    {{-- Ratings row --}}
                    @if($match->mvp || $match->economy_rating || $match->mechanical_rating || $match->match_score)
                        <div class="flex flex-wrap gap-3 text-sm">
                            @if($match->mvp)
                                <div class="flex items-center gap-1.5 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 px-2.5 py-1 rounded-lg">
                                    <flux:icon.trophy class="size-3.5" />
                                    <span class="font-medium">MVP:</span> {{ $match->mvp }}
                                </div>
                            @endif
                            @if($match->match_score)
                                <div class="flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 rounded-lg text-zinc-600 dark:text-zinc-400">
                                    <span class="font-medium">Match quality</span>
                                    <span class="font-bold text-zinc-900 dark:text-white">{{ $match->match_score }}/10</span>
                                </div>
                            @endif
                            @if($match->economy_rating)
                                <div class="flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 rounded-lg text-zinc-600 dark:text-zinc-400">
                                    <span class="font-medium">Economy</span>
                                    <span class="font-bold text-zinc-900 dark:text-white">{{ $match->economy_rating }}/10</span>
                                </div>
                            @endif
                            @if($match->mechanical_rating)
                                <div class="flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 rounded-lg text-zinc-600 dark:text-zinc-400">
                                    <span class="font-medium">Mechanics</span>
                                    <span class="font-bold text-zinc-900 dark:text-white">{{ $match->mechanical_rating }}/10</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Summary --}}
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ $match->ai_summary }}</p>

                    {{-- Key moments --}}
                    @if(!empty($match->key_moments))
                        <div>
                            <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-2">Key Moments</div>
                            <ul class="space-y-1">
                                @foreach($match->key_moments as $moment)
                                    <li class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                        <span class="text-zinc-400 mt-0.5">›</span>{{ $moment }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Similar matches --}}
                    @if(!empty($match->similar_match_ids))
                        @php
                            $similar = \App\Models\GameMatch::whereIn('id', $match->similar_match_ids)
                                ->get(['id', 'faceit_match_id', 'map', 'team_a_name', 'team_b_name', 'team_a_score', 'team_b_score', 'played_at']);
                        @endphp
                        @if($similar->isNotEmpty())
                            <div>
                                <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-2">Similar Matches</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($similar as $s)
                                        <div class="flex items-stretch text-xs bg-zinc-100 dark:bg-zinc-800 rounded-lg overflow-hidden">
                                            <button
                                                wire:click="openMatch({{ $s->id }})"
                                                class="px-2.5 py-1.5 text-left hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                                            >
                                                <span class="font-medium">{{ $s->map }}</span>
                                                <span class="text-zinc-500 ml-1">{{ $s->team_a_name }} {{ $s->team_a_score }}–{{ $s->team_b_score }} {{ $s->team_b_name }}</span>
                                            </button>
                                            @if($s->faceit_match_id)
                                                <a
                                                    href="https://www.faceit.com/en/cs2/room/{{ $s->faceit_match_id }}"
                                                    target="_blank"
                                                    class="flex items-center px-2 border-l border-zinc-200 dark:border-zinc-700 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                                                    wire:click.stop
                                                >
                                                    <flux:icon.arrow-top-right-on-square class="size-3" />
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            @endif
        @endif
    </flux:modal>
</div>
