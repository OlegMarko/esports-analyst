<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FindFaceitHubs extends Command
{
    protected $signature = 'faceit:hubs {query : Search term, e.g. "cs2" or a hub name}';

    protected $description = 'Search Faceit hubs and print their IDs';

    public function handle(): void
    {
        $query = $this->argument('query');

        $response = Http::baseUrl(config('services.faceit.base_url'))
            ->withHeaders([
                'Authorization' => 'Bearer ' . config('services.faceit.key'),
                'Accept'        => 'application/json',
            ])
            ->get('/search/hubs', [
                'name'  => $query,
                'game'  => 'cs2',
                'limit' => 10,
            ]);

        if ($response->failed()) {
            $this->error("API error: {$response->status()} — {$response->body()}");
            return;
        }

        $hubs = $response->json('items', []);

        if (empty($hubs)) {
            $this->warn('No hubs found.');
            return;
        }

        $rows = array_map(fn ($h) => [
            $h['competition_id'] ?? '–',
            $h['name']           ?? '–',
            $h['region']         ?? '–',
            number_format($h['number_of_members'] ?? 0),
        ], $hubs);

        $this->table(['Hub ID', 'Name', 'Region', 'Members'], $rows);

        $this->line('');
        $this->line('Add to .env:  FACEIT_HUB_IDS=' . implode(',', array_column($rows, 0)));
    }
}
