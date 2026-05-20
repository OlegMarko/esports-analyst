<?php

use App\Http\Controllers\FaceitWebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::post('webhooks/faceit', FaceitWebhookController::class)->name('webhooks.faceit');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('players', 'players')->name('players');
    Route::view('leaderboard', 'leaderboard')->name('leaderboard');
    Route::view('live', 'live')->name('live');
    Route::get('player/{nickname}', fn (string $nickname) => view('player', compact('nickname')))->name('player.show');
});

require __DIR__.'/settings.php';
