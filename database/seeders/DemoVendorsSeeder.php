<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Scopes\DemoScope;
use App\Models\User;
use App\Models\VendorSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds 12 plausible-sounding demo vendors with fake products,
 * all flagged is_demo = true so they only surface on demo.peptidemap.com.
 *
 * Idempotent — safe to re-run; uses firstOrCreate by slug.
 */
class DemoVendorsSeeder extends Seeder
{
    public function run(): void
    {
        // [name, country (US/UK/EU/CA), description, coupon, founded, payment]
        $vendors = [
            ['Helix Research Co.',       'United States',  'Independent peptide supplier focused on small-batch QC and direct lab-traceable COAs.', 'HELIX10',   2021, ['Credit Card','Cryptocurrency']],
            ['Northbridge Peptides',     'United Kingdom', 'UK-based laboratory supply with full third-party HPLC verification.',                  'NORTH15',   2019, ['Credit Card','Bank Transfer']],
            ['Vector Bio Labs',          'United States',  'High-purity research compounds for academic and private research applications.',         'VECTOR12',  2020, ['Credit Card','Cryptocurrency','PayPal']],
            ['Apex Molecular',           'Canada',         'Canadian research supply specializing in peptide hormones and growth factors.',          'APEX10',    2018, ['Credit Card','Cryptocurrency']],
            ['Cascade Biosynth',         'United States',  'Pacific Northwest peptide laboratory — synthesized and tested in-house.',                'CASCADE15', 2022, ['Credit Card','Bank Transfer']],
            ['Atlas Peptide Co.',        'United States',  'Wide catalog of research peptides with same-day shipping and bulk pricing.',             'ATLAS10',   2017, ['Credit Card','Cryptocurrency','PayPal']],
            ['Meridian Research Lab',    'United Kingdom', 'Boutique research-grade compounds verified by independent ISO-accredited labs.',         'MERIDIAN20',2020, ['Credit Card','Bank Transfer']],
            ['Sterling Bioscience',      'United States',  'Premium peptide supplier with rigorous batch testing and full traceability.',            'STERLING10',2016, ['Credit Card','Cryptocurrency']],
            ['Chronos Peptides',         'Germany',        'EU-based research supply with EHEDG-compliant cold-chain logistics.',                    'CHRONOS15', 2019, ['Credit Card','Bank Transfer']],
            ['Vanguard Bio',             'United States',  'Specializing in next-generation research peptides and bioactive compounds.',             'VANGUARD12',2021, ['Credit Card','Cryptocurrency','PayPal']],
            ['Nexus Research Supply',    'Canada',         'Vertically integrated peptide synthesis and direct-to-researcher distribution.',         'NEXUS10',   2020, ['Credit Card','Cryptocurrency']],
            ['Crestline Molecular',      'United States',  'Mountain West research lab with proprietary purification protocols.',                    'CRESTLINE15',2018,['Credit Card','Bank Transfer']],
        ];

        // [category_id, slug, name, sizes (mg), price_range_per_size]
        $compounds = [
            [3,   'bpc-157',         'BPC-157',          [5, 10],            [40, 90]],
            [87,  'tb-500',          'TB-500',           [5, 10],            [50, 110]],
            [51,  'ghk-cu',          'GHK-Cu',           [50, 100, 200],     [30, 95]],
            [525, 'semaglutide',     'Semaglutide',      [3, 5, 10],         [80, 200]],
            [526, 'tirzepatide',     'Tirzepatide',      [10, 15, 30],       [120, 280]],
            [524, 'retatrutide',     'Retatrutide',      [5, 10, 20],        [180, 400]],
            [126, 'sermorelin',      'Sermorelin',       [2, 5, 10],         [40, 110]],
            [363, 'cjc-1295',        'CJC-1295',         [2, 5, 10],         [35, 95]],
            [56,  'ipamorelin',      'Ipamorelin',       [2, 5, 10],         [40, 100]],
            [50,  'mots-c',          'MOTS-c',           [5, 10],            [60, 130]],
            [47,  'aod-9604',        'AOD-9604',         [2, 5],             [60, 140]],
            [437, 'cagrilintide',    'Cagrilintide',     [5, 10],            [180, 350]],
            [361, 'hexarelin',       'Hexarelin',        [2, 5, 10],         [35, 90]],
            [36,  'nad',             'NAD+',             [100, 500, 1000],   [40, 180]],
            [222, 'epithalon',       'Epithalon',        [10, 50, 100],      [35, 130]],
            [48,  'thymosin-alpha-1','Thymosin Alpha-1', [5, 10],            [70, 150]],
        ];

        $createdBrands = 0;
        $createdProducts = 0;

        foreach ($vendors as [$name, $country, $description, $coupon, $founded, $paymentMethods]) {
            $slug = Str::slug($name);
            $email = 'demo+' . $slug . '@peptidemap.com';

            // Use withoutGlobalScope so we can find existing demo records during reseed
            $existingBrand = Brand::withoutGlobalScope(DemoScope::class)->where('slug', $slug)->first();
            if ($existingBrand) {
                continue; // already seeded — skip
            }

            // 1) User
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'vendor',
                    'email_verified_at' => now(),
                ]
            );

            // 2) Brand (rating 3.8–4.9)
            $brand = Brand::create([
                'user_id' => $user->id,
                'name' => $name,
                'slug' => $slug,
                'is_active' => true,
                'is_demo' => true,
                'rating_average' => round(mt_rand(380, 490) / 100, 2),
                'rating_count' => mt_rand(15, 240),
                'shipping_time' => round(mt_rand(380, 490) / 100, 2),
                'customer_service' => round(mt_rand(380, 490) / 100, 2),
                'quality' => round(mt_rand(420, 495) / 100, 2),
                'cost' => round(mt_rand(380, 490) / 100, 2),
                'packaging' => round(mt_rand(380, 490) / 100, 2),
                'affiliate_url_template' => null, // demo vendors don't have real URLs
                'affiliate_tag' => Str::lower(Str::slug($name)),
            ]);
            $createdBrands++;

            // 3) Vendor settings
            $locationId = match ($country) {
                'United Kingdom' => 2,
                'Canada' => 3,
                'Germany' => 4,
                default => 1, // USA
            };
            // Try to find a real location id for the country; fall back to USA (1).
            $loc = \App\Models\Location::where('name', 'like', '%' . explode(' ', $country)[0] . '%')->first();
            if ($loc) $locationId = $loc->id;

            VendorSetting::create([
                'brand_id' => $brand->id,
                'location_id' => $locationId,
                'description' => $description,
                'contact_email' => $email,
                'website' => 'https://' . $slug . '.example.com',
                'shop_url' => 'https://' . $slug . '.example.com',
                'founded_year' => $founded,
                'coupon_code' => $coupon,
                'shipping_info' => 'Ships in 1–2 business days. Tracked international available.',
                'return_policy' => '30-day satisfaction guarantee on unopened items.',
                'business_hours' => 'Mon–Fri 9:00–17:00 local time',
                'payment_methods' => $paymentMethods,
                'status' => 1,
                'approval_status' => 'approved',
                'api_platform' => 'page_scrape',
                'is_partner' => mt_rand(0, 100) < 40, // ~40% are "partners"
                'featured' => mt_rand(0, 100) < 30,    // ~30% featured
                'top_vendor' => mt_rand(0, 100) < 20,  // ~20% top
            ]);

            // 4) Products — each vendor gets 8–14 random compounds
            $compoundCount = mt_rand(8, 14);
            $vendorCompounds = collect($compounds)->shuffle()->take($compoundCount);

            foreach ($vendorCompounds as $compound) {
                [$catId, $catSlug, $compoundName, $sizes, $priceRange] = $compound;

                // 1–3 size variants per compound
                $variantCount = min(count($sizes), mt_rand(1, 3));
                $variantSizes = collect($sizes)->shuffle()->take($variantCount);

                foreach ($variantSizes as $sizeMg) {
                    [$minPrice, $maxPrice] = $priceRange;
                    // scale price by size index (bigger size = higher price)
                    $sizeIdx = array_search($sizeMg, $sizes);
                    $priceFloor = $minPrice + ($sizeIdx * (($maxPrice - $minPrice) / max(count($sizes) - 1, 1)));
                    $price = round($priceFloor + mt_rand(-5, 10), 2);
                    if ($price < $minPrice) $price = $minPrice;

                    $productSlug = $slug . '-' . $catSlug . '-' . $sizeMg . 'mg';
                    $productName = $compoundName . ' ' . $sizeMg . 'mg';

                    Product::create([
                        'name' => $productName,
                        'slug' => $productSlug,
                        'description' => $compoundName . ' research peptide, ' . $sizeMg . 'mg vial. ' . $name . ' batch-tested for purity ≥98%.',
                        'price' => $price,
                        'discount_price' => mt_rand(0, 100) < 25 ? round($price * 0.85, 2) : null,
                        'brand_id' => $brand->id,
                        'location_id' => $locationId,
                        'product_category_id' => $catId,
                        'size_mg' => $sizeMg,
                        'purity' => mt_rand(0, 100) < 70 ? 98.5 : 99.0,
                        'availability' => 'in_stock',
                        'status' => 'active',
                        'hidden' => false,
                        'is_demo' => true,
                        'featured' => mt_rand(0, 100) < 15,
                        'lab_tested' => true,
                        'verified' => true,
                        'rating_average' => round(mt_rand(380, 495) / 100, 2),
                        'rating_count' => mt_rand(0, 80),
                        'product_url' => 'https://' . $slug . '.example.com/product/' . $productSlug,
                        'image_url' => null,
                    ]);
                    $createdProducts++;
                }
            }
        }

        $this->command->info("Created {$createdBrands} demo vendors with {$createdProducts} products.");
    }
}
