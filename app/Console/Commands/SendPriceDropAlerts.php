<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendPriceDropAlerts extends Command
{
    protected $signature = 'alerts:send-price-drops {--dry-run : Log recipients + drops without sending mail} {--threshold=5 : Minimum % drop to include (default 5)} {--cooldown=6 : Minimum days between digests to the same user (default 6)}';

    protected $description = 'Weekly digest — emails wishlisted-product owners when prices have dropped >=5% since they added the item. Products only (compound-level alerts are a later feature).';

    public function handle(): int
    {
        $threshold = (float) $this->option('threshold');
        $cooldown = (int) $this->option('cooldown');
        $dryRun = (bool) $this->option('dry-run');
        $now = Carbon::now();

        // Users with product wishlists on 'weekly' frequency + verified emails
        // (unverified users never receive marketing email — no exceptions).
        $userIds = Wishlist::where('wishable_type', 'product')
            ->where('alert_frequency', 'weekly')
            ->distinct()
            ->pluck('user_id');

        $sent = 0;
        $skippedCooldown = 0;
        $skippedNoDrops = 0;
        $skippedUnverified = 0;

        foreach ($userIds as $uid) {
            $user = User::find($uid);
            if (!$user) continue;
            if (!$user->email_verified_at) {
                $skippedUnverified++;
                continue;
            }

            $rows = Wishlist::where('user_id', $uid)
                ->where('wishable_type', 'product')
                ->where('alert_frequency', 'weekly')
                ->with('product.brand.vendorSetting')
                ->get();

            // Cooldown check — never digest a user twice in <cooldown days.
            $anyRecentAlert = $rows->max('last_alerted_at');
            if ($anyRecentAlert && Carbon::parse($anyRecentAlert)->gt($now->copy()->subDays($cooldown))) {
                $skippedCooldown++;
                continue;
            }

            $drops = [];
            foreach ($rows as $row) {
                $p = $row->product;
                if (!$p) continue;
                if ($row->last_seen_price === null || $row->last_seen_price <= 0) continue;

                $current = $p->discount_price && $p->discount_price < $p->price
                    ? (float) $p->discount_price
                    : (float) $p->price;

                if ($current <= 0) continue;
                $pctChange = (($current - (float) $row->last_seen_price) / (float) $row->last_seen_price) * 100;
                $dropPct = -$pctChange; // positive number = drop
                if ($dropPct < $threshold) continue;

                $drops[] = [
                    'wishlist_id' => $row->id,
                    'product' => $p,
                    'old_price' => (float) $row->last_seen_price,
                    'new_price' => $current,
                    'drop_pct' => round($dropPct, 1),
                ];
            }

            if (empty($drops)) {
                $skippedNoDrops++;
                continue;
            }

            if ($dryRun) {
                $this->line("[DRY-RUN] Would email {$user->email} — " . count($drops) . " drop(s):");
                foreach ($drops as $d) {
                    $this->line(sprintf(
                        '    • %s  $%.2f → $%.2f  (-%.1f%%)',
                        $d['product']->display_name ?? $d['product']->name,
                        $d['old_price'],
                        $d['new_price'],
                        $d['drop_pct']
                    ));
                }
                continue;
            }

            try {
                Mail::send('emails.price-drop-digest', [
                    'user' => $user,
                    'drops' => $drops,
                    'wishlistUrl' => url('/account/wishlist'),
                ], function ($msg) use ($user, $drops) {
                    $count = count($drops);
                    $msg->to($user->email, $user->name)
                        ->subject("{$count} price drop" . ($count === 1 ? '' : 's') . ' on your Peptidemap wishlist');
                });

                // Update last_alerted_at + last_seen_price so the next digest
                // compares against the new baseline (prevents re-alerting the
                // same drop next week).
                $ids = array_map(fn ($d) => $d['wishlist_id'], $drops);
                foreach ($drops as $d) {
                    Wishlist::where('id', $d['wishlist_id'])->update([
                        'last_alerted_at' => $now,
                        'last_seen_price' => $d['new_price'],
                    ]);
                }
                $sent++;
            } catch (\Throwable $e) {
                $this->error("Failed to send digest to {$user->email}: " . $e->getMessage());
            }
        }

        $mode = $dryRun ? ' [DRY-RUN]' : '';
        $this->info("Alerts run complete{$mode}. sent={$sent} skipped_cooldown={$skippedCooldown} skipped_no_drops={$skippedNoDrops} skipped_unverified={$skippedUnverified}");
        return self::SUCCESS;
    }
}
