<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('shop-hub:sync')->everyMinute()->withoutOverlapping();
Schedule::command('subscriptions:suspend-shops-without-plan')->dailyAt('00:10')->withoutOverlapping();
