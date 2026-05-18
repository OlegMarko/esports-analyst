<?php

namespace App\Jobs;

use App\Models\GameMatch;
use App\Services\FaceitNormalizer;
use App\Services\FaceitService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IngestMatchJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var int[] */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly string $faceitMatchId)
    {
        $this->onQueue('webhooks');
    }

    public function handle(FaceitService $faceit, FaceitNormalizer $normalizer): void
    {
        $details = $faceit->matchDetails($this->faceitMatchId);

        if (($details['status'] ?? '') !== 'FINISHED') {
            return;
        }

        $stats = $faceit->matchStats($this->faceitMatchId);

        $matchPayload = $normalizer->toMatchPayload($details, $stats);
        $playerPayloads = $normalizer->toPlayerPayloads($stats, $details);

        $match = GameMatch::create($matchPayload);
        $match->players()->createMany($playerPayloads);

        EmbedMatchJob::dispatch($match->id)
            ->onQueue('embeddings')
            ->chain([new GenerateSummaryJob($match->id)]);
    }
}
