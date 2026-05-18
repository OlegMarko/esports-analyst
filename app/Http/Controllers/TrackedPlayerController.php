<?php

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Models\TrackedPlayer;
use App\Services\FaceitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackedPlayerController extends Controller
{
    public function __construct(private readonly FaceitService $faceit) {}

    public function index(): View
    {
        $players = TrackedPlayer::orderBy('elo', 'desc')->get();

        return view('players.index', compact('players'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['nickname' => ['required', 'string', 'max:64']]);

        try {
            $data = $this->faceit->playerByNickname($request->nickname);
        } catch (NotFoundException) {
            return back()->withErrors(['nickname' => 'Player not found on Faceit.']);
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

        return back()->with('success', "Player {$data['nickname']} is now being tracked.");
    }

    public function destroy(TrackedPlayer $player): RedirectResponse
    {
        $player->delete();

        return back()->with('success', "Player {$player->faceit_nickname} removed.");
    }
}
