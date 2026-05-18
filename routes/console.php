<?php

use App\Jobs\PollFaceitMatchesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new PollFaceitMatchesJob)
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('faceit-poll');
