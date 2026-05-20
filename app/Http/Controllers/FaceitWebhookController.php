<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFaceitWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FaceitWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->json()->all();

        // Optional: reject requests from wrong app
        $expectedAppId = config('services.faceit.app_id');
        if ($expectedAppId && ($payload['app_id'] ?? '') !== $expectedAppId) {
            abort(403);
        }

        ProcessFaceitWebhookJob::dispatch($payload)->onQueue('webhooks');

        return response('', 200);
    }
}
