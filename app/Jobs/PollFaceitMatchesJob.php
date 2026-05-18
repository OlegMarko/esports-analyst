<?php

namespace App\Jobs;

use App\Exceptions\RateLimitException;
use App\Models\TrackedPlayer;
use App\Services\FaceitService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class PollFaceitMatchesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('webhooks');
    }

    public function handle(FaceitService $faceit): void
    {
        $players = TrackedPlayer::active()->get();

        foreach ($players as $tracked) {
            try {
                $matches = $faceit->playerMatches($tracked->faceit_id, limit: 10);

                foreach ($matches as $rawMatch) {
                    $key = "match_ingested:{$rawMatch['match_id']}";

                    if (Redis::exists($key) > 0) {
                        continue;
                    }

                    Redis::setex($key, 86400 * 7, 1);

                    IngestMatchJob::dispatch($rawMatch['match_id'])->onQueue('webhooks');
                }

                $tracked->update(['last_polled_at' => now()]);
            } catch (RateLimitException) {
                Log::warning('Faceit rate limit hit, backing off');
                break;
            } catch (\Exception $e) {
                Log::error("Failed polling {$tracked->faceit_nickname}: {$e->getMessage()}");
                continue;
            }
        }
    }
}
