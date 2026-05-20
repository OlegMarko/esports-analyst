<?php

namespace App\Livewire;

use App\Exceptions\NotFoundException;
use App\Models\TrackedPlayer;
use App\Services\FaceitService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class PlayerTracker extends Component
{
    #[Validate('required|string|max:64')]
    public string $nickname = '';

    public string $success = '';

    public string $error = '';

    #[Computed]
    public function players(): \Illuminate\Database\Eloquent\Collection
    {
        return TrackedPlayer::orderByDesc('elo')->get();
    }

    public function addPlayer(): void
    {
        $this->validate();
        $this->success = '';
        $this->error = '';

        try {
            $data = app(FaceitService::class)->playerByNickname($this->nickname);
        } catch (NotFoundException) {
            $this->error = 'Player not found on Faceit';
            return;
        }

        TrackedPlayer::firstOrCreate(
            ['faceit_id' => $data['player_id']],
            [
                'faceit_nickname' => $data['nickname'],
                'steam_id' => $data['steam_id_64'] ?? null,
                'avatar' => $data['avatar'] ?? null,
                'faceit_level' => $data['games']['cs2']['skill_level'] ?? 1,
                'elo' => $data['games']['cs2']['faceit_elo'] ?? 1000,
            ]
        );

        $this->success = "Added {$data['nickname']}";
        $this->nickname = '';
        Flux::toast($this->success);
    }

    public function removePlayer(int $id): void
    {
        TrackedPlayer::findOrFail($id)->delete();
        Flux::toast('Player removed.');
    }

    public function render()
    {
        return view('livewire.player-tracker');
    }
}
