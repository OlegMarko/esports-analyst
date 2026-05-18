<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateSummaryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public readonly int $matchId)
    {
        $this->onQueue('summaries');
    }

    public function handle(): void
    {
        // Step 5 will implement AI summary generation.
        Log::info("Summary queued for match {$this->matchId}");
    }
}
