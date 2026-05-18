<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\MatchAspectEmbedding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HybridMatchRetriever
{
    public function retrieve(string $query, string $game, int $topN = 20): Collection
    {
        $vector = $this->vectorSearch($query, $game, $topN);
        $keyword = $this->keywordSearch($query, $game, $topN);

        return $this->reciprocalRankFusion($vector, $keyword, $topN);
    }

    private function vectorSearch(string $query, string $game, int $limit): Collection
    {
        return MatchAspectEmbedding::query()
            ->join('matches', 'matches.id', '=', 'match_aspect_embeddings.match_id')
            ->where('match_aspect_embeddings.game', $game)
            ->whereVectorSimilarTo('match_aspect_embeddings.embedding', $query)
            ->select('matches.*')
            ->limit($limit * 2)
            ->get()
            ->unique('id');
    }

    private function keywordSearch(string $query, string $game, int $limit): Collection
    {
        return DB::table('matches')
            ->whereRaw(
                "to_tsvector('english',
                   coalesce(ai_summary,'') || ' ' ||
                   coalesce(map,'') || ' ' ||
                   coalesce(team_a_name,'') || ' ' ||
                   coalesce(team_b_name,''))
                 @@ plainto_tsquery('english', ?)",
                [$query]
            )
            ->where('game', $game)
            ->selectRaw(
                "id, ts_rank(
                   to_tsvector('english', coalesce(ai_summary,'') || ' ' || coalesce(map,'')),
                   plainto_tsquery('english', ?)
                 ) as rank",
                [$query]
            )
            ->orderByDesc('rank')
            ->limit($limit * 2)
            ->get();
    }

    private function reciprocalRankFusion(Collection $vector, Collection $keyword, int $topN): Collection
    {
        $scores = [];

        foreach ($vector->values() as $rank => $item) {
            $id = $item->id;
            $scores[$id] = ($scores[$id] ?? 0.0) + 1 / (60 + $rank + 1);
        }

        foreach ($keyword->values() as $rank => $item) {
            $id = $item->id;
            $scores[$id] = ($scores[$id] ?? 0.0) + 1 / (60 + $rank + 1);
        }

        arsort($scores);

        $topIds = array_keys(array_slice($scores, 0, $topN, true));

        return GameMatch::with('players')
            ->whereIn('id', $topIds)
            ->get()
            ->sortBy(fn ($m) => array_search($m->id, $topIds))
            ->values();
    }
}
