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
                @php
                    $mvp       = $match->players->sortByDesc('kills')->first();
                    $topKda    = $match->players->sortByDesc('kda')->first();
                    $avgAdr    = $match->players->avg('adr');
                @endphp

                <flux:card
                    wire:click="selectMatch({{ $match->id }})"
                    class="cursor-pointer transition-all hover:shadow-md flex flex-col gap-0"
                >
                    {{-- Header row --}}
                    <div class="flex items-center justify-between mb-3">
                        <flux:badge color="zinc" size="sm">{{ $match->map }}</flux:badge>
                        @if($match->ai_summary)
                            <flux:badge color="green" size="sm" icon="sparkles">AI Ready</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Pending</flux:badge>
                        @endif
                    </div>

                    {{-- Score --}}
                    <div class="mb-3">
                        <div class="text-sm text-zinc-500 truncate">{{ $match->team_a_name }}</div>
                        <div class="text-2xl font-bold tracking-tight font-mono">
                            {{ $match->team_a_score }}
                            <span class="text-zinc-400 text-lg">–</span>
                            {{ $match->team_b_score }}
                        </div>
                        <div class="text-sm text-zinc-500 truncate">{{ $match->team_b_name }}</div>
                    </div>

                    {{-- AI Headline --}}
                    @if($match->headline)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 italic leading-snug mb-2">{{ $match->headline }}</p>
                    @endif

                    {{-- Stats strip --}}
                    @if($mvp)
                        <div class="border-t border-zinc-100 dark:border-zinc-700 pt-3 mt-auto grid grid-cols-3 gap-2 text-center">
                            <div>
                                <div class="text-xs text-zinc-400 mb-0.5">MVP</div>
                                <div class="text-sm font-semibold truncate" title="{{ $mvp->faceit_nickname }}">{{ $mvp->faceit_nickname }}</div>
                                <div class="text-xs text-zinc-500">{{ $mvp->kills }}K / {{ $mvp->deaths }}D</div>
                            </div>
                            <div>
                                <div class="text-xs text-zinc-400 mb-0.5">Top KDA</div>
                                <div class="text-sm font-semibold">{{ number_format($topKda->kda, 2) }}</div>
                                <div class="text-xs text-zinc-500 truncate">{{ $topKda->faceit_nickname }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-zinc-400 mb-0.5">Avg ADR</div>
                                <div class="text-sm font-semibold">{{ round($avgAdr) }}</div>
                                @if($match->eco_round_count)
                                    <div class="text-xs text-zinc-500">{{ $match->eco_round_count }} eco</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Footer --}}
                    <div class="text-xs text-zinc-400 mt-3">
                        {{ $match->played_at?->format('M d, Y') }}
                        @if($match->duration_minutes)
                            · {{ $match->duration_minutes }}m
                        @endif
                        @if($match->first_half_score && $match->second_half_score)
                            · {{ $match->first_half_score }} / {{ $match->second_half_score }}
                        @endif
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif

    {{-- Match detail modal --}}
    <flux:modal name="match-detail" class="w-full max-w-3xl" scroll="body">
        @if($this->selectedMatch)
            @php $match = $this->selectedMatch; @endphp

            {{-- Header --}}
            <div class="mb-6">
                <div class="flex items-center gap-3 mb-2">
                    <flux:badge color="zinc">{{ $match->map }}</flux:badge>
                    @if($match->ai_summary)
                        <flux:badge color="green" icon="sparkles">AI Ready</flux:badge>
                    @else
                        <flux:badge color="zinc">Pending</flux:badge>
                    @endif
                    <span class="text-zinc-500 text-sm">{{ $match->played_at?->format('M d, Y · H:i') }}</span>
                    @if($match->duration_minutes)
                        <span class="text-zinc-500 text-sm">· {{ $match->duration_minutes }}min</span>
                    @endif
                </div>
                <div class="text-2xl font-bold">
                    {{ $match->team_a_name }}
                    <span class="font-mono px-2 text-zinc-400">{{ $match->team_a_score }} – {{ $match->team_b_score }}</span>
                    {{ $match->team_b_name }}
                </div>
                @if($match->first_half_score && $match->second_half_score)
                    <div class="text-sm text-zinc-500 mt-1">Halves: {{ $match->first_half_score }} / {{ $match->second_half_score }}</div>
                @endif
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
                                    <flux:table.row :key="$p->id">
                                        <flux:table.cell variant="strong">
                                            <a href="{{ route('player.show', $p->faceit_nickname) }}" wire:navigate class="hover:underline">
                                                {{ $p->faceit_nickname }}
                                            </a>
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
                    {{-- Section header --}}
                    <div class="flex items-center gap-2">
                        <flux:icon.sparkles class="text-yellow-500 size-4" />
                        <span class="text-sm font-medium">AI Analysis</span>
                    </div>

                    {{-- Headline --}}
                    @if($match->headline)
                        <p class="text-base font-semibold text-zinc-800 dark:text-zinc-100 leading-snug">{{ $match->headline }}</p>
                    @endif

                    {{-- Ratings row --}}
                    @if($match->mvp || $match->economy_rating || $match->mechanical_rating || $match->match_score)
                        <div class="flex flex-wrap gap-2 text-sm">
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
                                        <span class="text-zinc-400 mt-0.5 shrink-0">›</span>{{ $moment }}
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
                                                wire:click="selectMatch({{ $s->id }})"
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
            @else
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 flex items-center gap-3 text-zinc-500">
                    <flux:icon.arrow-path class="size-4 animate-spin" />
                    <span class="text-sm">Generating AI analysis — this may take a minute.</span>
                </div>
            @endif
        @endif
    </flux:modal>
</div>
