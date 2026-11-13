<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Every withoutOverlapping() below sets a lock expiry in minutes. The default
// is 24 hours, so a job killed mid-run (deploy, reboot) silently skipped its
// next runs for a day. Each window is shorter than the job's own interval
// for daily/weekly jobs and comfortably longer than a normal run.

// Apply frontend cache busts that were deferred by the once-a-minute
// throttle in AppServiceProvider::queueFrontendBust().
Schedule::call(fn () => \App\Providers\AppServiceProvider::flushPendingFrontendBust())
    ->everyMinute()
    ->name('frontend-cache-flush');

// Schedule automatic scraping based on frequency settings
Schedule::command('scraping:run-scheduled')
    ->everyMinute()
    ->withoutOverlapping(30)
    ->runInBackground();

// Mirror vendor product images onto our own storage. Vendors (Bioedge,
// Certified-Pep) 403 the browser's cross-origin <img> loads via
// WordPress hotlink protection + IP/UA firewall rules, so hotlinked
// images render as broken fallbacks on some viewer networks. Hourly
// run picks up anything new from the scraper within the hour; limit
// keeps a single run bounded so network stalls never cascade.
Schedule::command('products:sync-external-images --limit=200')
    ->hourly()
    ->withoutOverlapping(55)
    ->runInBackground();

// Weekly growth digest to Discord — every Monday 09:00 UTC (04:00 ET / 01:00 PT).
// Pulls from GrowthMetrics service; posts a summary card to the growth channel.
Schedule::command('growth:digest')
    ->weeklyOn(1, '09:00')
    ->timezone('UTC')
    ->withoutOverlapping(180);

// Auto-revert expired coupon boosts back to the vendor's standard %.
// Every 5 minutes so a boost expires within 5 min of its declared time.
Schedule::command('coupons:revert-expired-boosts')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

// Promote scheduled coupon boosts to active when their start time
// hits. Every 5 minutes so a boost begins within 5 min of the
// declared start. Colin Sep 14 — Julia's PMAP feedback.
Schedule::command('coupons:activate-scheduled-boosts')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

// Deactivate stackable VendorPromotion rows past their ends_at.
// Colin/Julia PMAP Sep 30: Instant Peptides' BOGO stayed live past
// its end date because is_active was never flipped. isLive() on
// the model already excluded them from public queries, but the
// admin view still counted them as active.
Schedule::command('promotions:revert-expired')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

// Refresh external review aggregates (Reviews.io, Trustpilot, PepReviewPro)
// weekly. Ratings don't move day-to-day; hourly would be noisy scraping.
Schedule::command('reviews:refresh')
    ->weeklyOn(2, '04:00')  // Tuesday 04:00 UTC — off-peak
    ->timezone('UTC')
    ->withoutOverlapping(180);

// Wishlist / price-alert engine.
// Daily snapshot at 03:00 UTC captures every active product's price
// (deduped when unchanged so the table stays small).
Schedule::command('products:snapshot-prices')
    ->dailyAt('03:00')
    ->withoutOverlapping(180)
    ->runInBackground();

// Digest email at 09:00 UTC — one email per user grouping all their
// drops. Cooldown + dry-run flags are baked into the command itself.
Schedule::command('alerts:send-price-drops')
    ->dailyAt('09:00')
    ->withoutOverlapping(180)
    ->runInBackground();

// Daily vendor feed sync — re-fetch every vendor's saved feed URL
// and upsert products. Colin/Rudy Sep 22: without this the /vendor
// import UI was one-shot and stock/pricing went stale silently.
// 05:00 UTC (01:00 ET) — off-peak.
Schedule::command('feeds:sync-vendors')
    ->dailyAt('05:00')
    ->withoutOverlapping(180)
    ->runInBackground();

// Purgo Labs targeted scraper — no feed URL, no Woo, so the generic
// pipeline undercounts. JSON-LD walk of purgolabs.com/products.
// 05:30 UTC (01:30 ET) — after the generic feed sync.
Schedule::command('purgo:sync')
    ->dailyAt('05:30')
    ->withoutOverlapping(180)
    ->runInBackground();

// Australian Research Peptides — well-structured llms.txt + per-product
// /text.md mirrors with price/stock/SKU tables. 05:45 UTC after Purgo.
Schedule::command('arp:sync')
    ->dailyAt('05:45')
    ->withoutOverlapping(180)
    ->runInBackground();

// Zenith Bioscience — public REST catalog (PRICETOOL_CATALOG_API_CONTRACT),
// Bearer-auth. Contract polls 1-4x/day; we align with that cadence.
Schedule::command('zenith:sync')
    ->dailyAt('06:00')
    ->withoutOverlapping(180)
    ->runInBackground();

// Vistara Labs — central price library at /peptide-library/prices/.
// Vendor-recommended source. 06:30 UTC after Zenith.
Schedule::command('vistara:sync')
    ->dailyAt('06:30')
    ->withoutOverlapping(180)
    ->runInBackground();

// My Peptides UK — public paginated JSON API, no auth. Skip
// is_unlisted products per their note.
Schedule::command('mypeptides:sync')
    ->dailyAt('06:15')
    ->withoutOverlapping(180)
    ->runInBackground();

// Daily affiliate-stats pull. Hits each configured vendor's affiliate
// program API (GoAffPro today; Refersion/Impact clients to add) and
// caches the snapshot on vendor_settings.affiliate_stats_json. Powers
// the /admin/affiliates dashboard without re-pulling on every load.
// 04:00 UTC keeps it away from the scraper + digest windows.
Schedule::command('affiliates:sync')
    ->dailyAt('04:00')
    ->withoutOverlapping(180)
    ->runInBackground();
