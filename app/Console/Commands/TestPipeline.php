<?php

namespace App\Console\Commands;

use App\Jobs\EmbedMatchJob;
use App\Jobs\GenerateSummaryJob;
use App\Jobs\UpdateLeaderboardsJob;
use App\Models\GameMatch;
use App\Models\MatchAspectEmbedding;
use App\Models\Player;
use App\Models\TrackedPlayer;
use App\Services\LeaderboardService;
use Illuminate\Console\Command;

class TestPipeline extends Command
{
    protected $signature = 'ai:test-pipeline';

    protected $description = 'Test the AI analysis pipeline end-to-end with factory data (no Faceit API calls).';

    public function handle(): int
    {
        $allPassed = true;

        // 1. Tracked player
        $this->info('[1/7] Creating test player...');
        $player = TrackedPlayer::factory()->create();
        $this->line("       → {$player->faceit_nickname}");

        // 2. Match + 10 players
        $this->info('[2/7] Creating test match with 10 players...');
        $match = GameMatch::factory()
            ->has(Player::factory(10), 'players')
            ->create();
        $this->line("       → {$match->team_a_name} vs {$match->team_b_name} on {$match->map}");

        // 3. Embed
        $this->info('[3/7] Running EmbedMatchJob (Ollama nomic-embed-text)...');
        try {
            (new EmbedMatchJob($match->id))->handle();
            $this->line('       → OK');
        } catch (\Throwable $e) {
            $this->error("       → FAIL: {$e->getMessage()}");
            $allPassed = false;
        }

        // 4. Assert 4 aspect embeddings
        $this->info('[4/7] Checking 4 aspect embeddings created...');
        $count = MatchAspectEmbedding::where('match_id', $match->id)->count();
        if ($count === 4) {
            $this->line('       → OK (4 aspect embeddings)');
        } else {
            $this->error("       → FAIL: expected 4, got {$count}");
            $allPassed = false;
        }

        // 5. Generate summary + leaderboard update
        $this->info('[5/7] Running GenerateSummaryJob (Ollama llama3.2)...');
        try {
            app()->call([new GenerateSummaryJob($match->id), 'handle']);
            // UpdateLeaderboardsJob is dispatched to queue inside the job; run it here synchronously.
            app()->call([new UpdateLeaderboardsJob($match->id), 'handle']);
            $this->line('       → OK');
        } catch (\Throwable $e) {
            $this->error("       → FAIL: {$e->getMessage()}");
            $allPassed = false;
        }

        // 6. Assert ai_summary populated
        $this->info('[6/7] Checking AI summary generated...');
        $match->refresh();
        if (! empty($match->ai_summary)) {
            $this->line('       → OK');
        } else {
            $this->error('       → FAIL: ai_summary is null');
            $allPassed = false;
        }

        // 7. Check leaderboard has entries
        $this->info('[7/7] Checking leaderboard data...');
        $top = app(LeaderboardService::class)->top('cs2', 'kda', 5);
        if (! empty($top)) {
            $this->line('       → OK (' . count($top) . ' entries)');
        } else {
            $this->error('       → FAIL: no leaderboard entries');
            $allPassed = false;
        }

        $this->newLine();

        if ($allPassed) {
            $this->info('All pipeline checks passed.');
            return self::SUCCESS;
        }

        $this->error('Some checks failed.');
        return self::FAILURE;
    }
}
