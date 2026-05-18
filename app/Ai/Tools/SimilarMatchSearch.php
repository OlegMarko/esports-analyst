<?php

namespace App\Ai\Tools;

use App\Models\MatchAspectEmbedding;
use Laravel\Ai\Tools\SimilaritySearch;

class SimilarMatchSearch
{
    public static function make(string $game): SimilaritySearch
    {
        return (new SimilaritySearch(
            using: function (string $query) use ($game) {
                return MatchAspectEmbedding::query()
                    ->with('match.players')
                    ->where('game', $game)
                    ->whereVectorSimilarTo('embedding', $query, minSimilarity: 0.65)
                    ->limit(12)
                    ->get()
                    ->map(fn ($e) => $e->match)
                    ->filter()
                    ->unique('id')
                    ->values()
                    ->each(fn ($match) => $match->makeHidden(['raw_faceit_payload', 'ai_summary']));
            }
        ))->withDescription(
            'Search historical CS2 Faceit matches by playstyle, map, economy, or player performance patterns.'
        );
    }
}
