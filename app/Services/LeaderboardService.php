<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class LeaderboardService
{
    private string $prefix = 'cs2_lb';

    private int $ttl = 3600;

    public function record(Player $player, string $game, string $metric, float $value): void
    {
        $key = "{$this->prefix}:{$game}:{$metric}";

        Redis::zadd($key, $value, $player->faceit_nickname);
        Redis::expire($key, $this->ttl);
    }

    public function top(string $game, string $metric, int $limit = 10): array
    {
        $key = "{$this->prefix}:{$game}:{$metric}";

        return Cache::remember(
            "lb_top:{$game}:{$metric}:{$limit}",
            300,
            fn () => Redis::zrevrange($key, 0, $limit - 1, true),
        );
    }

    public function playerRank(string $nickname, string $game, string $metric): ?int
    {
        $rank = Redis::zrevrank("{$this->prefix}:{$game}:{$metric}", $nickname);

        return $rank !== null ? $rank + 1 : null;
    }
}
