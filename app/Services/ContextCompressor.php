<?php

namespace App\Services;

use App\Models\GameMatch;
use Illuminate\Support\Collection;

use function Laravel\Ai\agent;

class ContextCompressor
{
    public function compress(string $query, Collection $matches): string
    {
        return $matches
            ->map(function (GameMatch $match) use ($query) {
                if (! $match->ai_summary) {
                    return null;
                }

                $response = agent(
                    instructions: 'Extract only the 1-2 sentences from the document most relevant '
                        . 'to the query. Return only those sentences, nothing else.'
                )->prompt(
                    "Query: {$query}\n\nDocument: {$match->ai_summary}",
                    provider: config('ai.compressor_provider'),
                    model: config('ai.compressor_model'),
                );

                return "Match {$match->id} ({$match->map} {$match->team_a_score}-{$match->team_b_score}): "
                    . (string) $response;
            })
            ->filter()
            ->join("\n");
    }
}
