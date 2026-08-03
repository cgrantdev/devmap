<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductPriceSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SnapshotProductPrices extends Command
{
    protected $signature = 'products:snapshot-prices {--force : Snapshot even if the last snapshot is identical}';

    protected $description = 'Snapshot current price of every visible+active product with a price. Dedupes identical (price, discount_price) tuples so the table stays free of noise.';

    public function handle(): int
    {
        $now = Carbon::now();
        $inserted = 0;
        $skipped = 0;

        Product::visible()
            ->where('status', 'active')
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->select('id', 'price', 'discount_price')
            ->chunkById(500, function ($products) use (&$inserted, &$skipped, $now) {
                foreach ($products as $p) {
                    $last = ProductPriceSnapshot::where('product_id', $p->id)
                        ->latest('snapshot_at')
                        ->first();

                    $samePrice = $last
                        && (float) $last->price === (float) $p->price
                        && ((float) ($last->discount_price ?? 0)) === ((float) ($p->discount_price ?? 0));

                    if ($samePrice && !$this->option('force')) {
                        $skipped++;
                        continue;
                    }

                    ProductPriceSnapshot::create([
                        'product_id' => $p->id,
                        'price' => $p->price,
                        'discount_price' => $p->discount_price,
                        'snapshot_at' => $now,
                    ]);
                    $inserted++;
                }
            });

        $this->info("Snapshot complete. inserted={$inserted} skipped_unchanged={$skipped}");
        return self::SUCCESS;
    }
}
