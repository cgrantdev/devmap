<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic scraping based on frequency settings
Schedule::command('scraping:run-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Weekly growth digest to Discord — every Monday 09:00 UTC (04:00 ET / 01:00 PT).
// Pulls from GrowthMetrics service; posts a summary card to the growth channel.
Schedule::command('growth:digest')
    ->weeklyOn(1, '09:00')
    ->timezone('UTC')
    ->withoutOverlapping();

// Wishlist / price-alert engine.
// Daily snapshot at 03:00 UTC captures every active product's price
// (deduped when unchanged so the table stays small).
Schedule::command('products:snapshot-prices')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground();

// Digest email at 09:00 UTC — one email per user grouping all their
// drops. Cooldown + dry-run flags are baked into the command itself.
Schedule::command('alerts:send-price-drops')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();
