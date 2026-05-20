<?php

namespace App\Livewire;

use App\Exceptions\NotFoundException;
use App\Jobs\GeneratePlayerBriefJob;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\TrackedPlayer;
use App\Services\FaceitService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PlayerProfile extends Component
{
    public string $nickname;

    public ?int $selectedMatchId = null;

    #[Computed]
    public function trackedPlayer(): ?TrackedPlayer
    {
        return TrackedPlayer::where('faceit_nickname', $this->nickname)->first();
    }

    #[Computed]
    public function faceitPlayerId(): ?string
    {
        return $this->trackedPlayer?->faceit_id
            ?? Player::where('faceit_nickname', $this->nickname)->value('faceit_player_id');
    }

    #[Computed]
    public function recentMatches(): Collection
    {
        if (! $this->faceitPlayerId) {
            return new Collection();
        }

        return Player::where('faceit_player_id', $this->faceitPlayerId)
            ->with('match')
            ->join('matches', 'players.match_id', '=', 'matches.id')
            ->orderByDesc('matches.played_at')
            ->select('players.*')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        if (! $this->faceitPlayerId) {
            return [];
        }

        $rows = Player::where('faceit_player_id', $this->faceitPlayerId)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $wins = $rows->where('result', 'win')->count();

        return [
            'matches'     => $rows->count(),
            'wins'        => $wins,
            'win_rate'    => round($wins / $rows->count() * 100),
            'avg_kda'     => round($rows->avg('kda'), 2),
            'avg_adr'     => round((float) $rows->avg('adr'), 1),
            'avg_kills'   => round((float) $rows->avg('kills'), 1),
            'avg_hs'      => round((float) $rows->avg('hs_percent'), 1),
            'total_kills' => $rows->sum('kills'),
        ];
    }

    #[Computed]
    public function selectedMatch(): ?GameMatch
    {
        return $this->selectedMatchId
            ? GameMatch::with('players')->find($this->selectedMatchId)
            : null;
    }

    public function openMatch(int $matchId): void
    {
        $this->selectedMatchId = $matchId;
        Flux::modal('match-detail')->show();
    }

    public function trackPlayer(): void
    {
        if ($this->trackedPlayer) {
            return;
        }

        try {
            $data = app(FaceitService::class)->playerByNickname($this->nickname);
        } catch (NotFoundException) {
            Flux::toast('Player not found on Faceit.', variant: 'danger');
            return;
        }

        TrackedPlayer::firstOrCreate(
            ['faceit_id' => $data['player_id']],
            [
                'faceit_nickname' => $data['nickname'],
                'steam_id'        => $data['steam_id_64'] ?? null,
                'avatar'          => $data['avatar'] ?? null,
                'faceit_level'    => $data['games']['cs2']['skill_level'] ?? 1,
                'elo'             => $data['games']['cs2']['faceit_elo'] ?? 1000,
            ]
        );

        unset($this->trackedPlayer);
        Flux::toast("{$this->nickname} is now being tracked.");
    }

    public function regenerateBrief(): void
    {
        $tp = $this->trackedPlayer;

        if (! $tp) {
            Flux::toast('Track this player first to generate a brief.', variant: 'warning');
            return;
        }

        GeneratePlayerBriefJob::dispatch($tp->id)->onQueue('summaries');
        Flux::toast('Performance brief queued — refresh in a moment.');
    }

    public function render()
    {
        return view('livewire.player-profile');
    }
}
