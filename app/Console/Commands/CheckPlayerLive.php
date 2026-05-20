<?php

namespace App\Console\Commands;

use App\Services\FaceitService;
use Illuminate\Console\Command;

class CheckPlayerLive extends Command
{
    protected $signature = 'faceit:check {nickname}';

    protected $description = 'Check if a player is currently in a live match';

    public function handle(FaceitService $faceit): void
    {
        $nickname = $this->argument('nickname');

        $this->info("Looking up {$nickname}...");
        $player = $faceit->playerByNickname($nickname);
        $id = $player['player_id'];
        $this->line("  faceit_id : {$id}");
        $this->line("  ELO       : " . ($player['games']['cs2']['faceit_elo'] ?? '?'));
        $this->newLine();

        $this->info('Fetching last 5 history entries...');
        $history = $faceit->playerMatches($id, limit: 5);

        if (empty($history)) {
            $this->warn('  No history returned.');
            return;
        }

        foreach ($history as $item) {
            $matchId = $item['match_id'] ?? '?';
            $this->line("  match_id : {$matchId}");

            try {
                $details = $faceit->matchDetails($matchId);
                $status  = $details['status'] ?? 'UNKNOWN';
                $map     = $details['voting']['map']['pick'][0]
                    ?? $details['voting']['map']['entities'][0]['guid']
                    ?? '?';
                $this->line("    status : {$status}");
                $this->line("    map    : {$map}");

                if (in_array($status, ['ONGOING', 'READY', 'SCHEDULED', 'VOTING'])) {
                    $this->newLine();
                    $this->info("  >>> LIVE MATCH FOUND: {$matchId} ({$status})");
                }
            } catch (\Exception $e) {
                $this->warn("    error  : {$e->getMessage()}");
            }

            $this->newLine();
        }
    }
}
