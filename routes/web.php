<?php

use App\Http\Controllers\TrackedPlayerController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/players', [TrackedPlayerController::class, 'index'])->name('players.index');
Route::post('/players', [TrackedPlayerController::class, 'store'])->name('players.store');
Route::delete('/players/{player}', [TrackedPlayerController::class, 'destroy'])->name('players.destroy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
