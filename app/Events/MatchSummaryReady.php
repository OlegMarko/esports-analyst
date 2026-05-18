<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchSummaryReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $matchId,
        public readonly array $analysis,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("match.{$this->matchId}");
    }

    public function broadcastAs(): string
    {
        return 'match.summary.ready';
    }

    public function broadcastWith(): array
    {
        return [
            'matchId' => $this->matchId,
            'analysis' => $this->analysis,
        ];
    }
}
