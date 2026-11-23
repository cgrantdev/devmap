<?php

use App\Models\ProductCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Files Capital Precision Labs' imported products under the right compound.
 *
 * The importer files a new product under the longest category alias found
 * inside its name, so short aliases caught longer names: Semax went under
 * Semaglutide, Dihexa under Hexarelin, Teriparatide under Ipamorelin and
 * TB-500 Fragment under TB-500. Blends went under their first compound
 * although blend pages exist, and products with a compare page of their
 * own (KLOW, Melanotan I and II, the Khavinson bioregulators, ...) got no
 * compound at all.
 *
 * The rules match this vendor's own product names. A target that does not
 * resolve to an active compare category is skipped. A null target takes a
 * product off a compound it does not belong to (it stays on the vendor
 * page). A hidden row with no compound, which is how "Discover Products"
 * hid it, is shown once it gets one.
 *
 * The aliases "semax", "dihexa" and "teriparatide" are added so future
 * imports, for every vendor, file those names correctly. Products already
 * filed for other vendors are not changed here.
 *
 * down() restores each product's compound and visibility and the aliases.
 */
return new class extends Migration
{
    private const BRAND_SLUG = 'capital-precision-labs';

    private const BACKUP_KEY = 'capital_precision_labs_compounds_backup';

    private const BIOREGULATORS = [
        'Bronchogen', 'Cartalax', 'Chonluten', 'Cortagen', 'Crystagen', 'Livagen',
        'Ovagen', 'Pancragen', 'Prostamax', 'Testagen', 'Vesugen', 'Vilon',
    ];

    private const ALIASES = ['semax' => 'semax', 'dihexa' => 'dihexa', 'teriparatide' => 'teriparatide'];

    /**
     * Name pattern => compare slug, or null for no compound. First match wins.
     *
     * @return list<array{0: string, 1: string|null}>
     */
    private function rules(): array
    {
        $rules = [
            ['~^Semax \+ Selank Blend~i', 'selank-semax'],
            ['~^(N-Acetyl\s+)?Semax\b~i', 'semax'],
            ['~^Dihexa\b~i', 'dihexa'],
            ['~^Teriparatide\b~i', 'teriparatide'],
            ['~^TB-500 Fragment\b~i', null],
            ['~^BPC-157 \+ TB-500 Blend~i', 'bpc-157-tb-500'],
            ['~^CJC-1295 \+ Ipamorelin Blend~i', 'cjc-1295-ipamorelin'],
            ['~^Tesamorelin \+ Ipamorelin Blend~i', 'tesamorelin-ipamorelin'],
            ['~^Tesa \+ Ipa \+ CJC Blend~i', null],
            ['~^Retatrutide \+ Tirzepatide Blend~i', 'retatrutide-tirzepatide-blend'],
            ['~^Retatrutide \+ Cagrilintide Blend~i', 'retatrutide-cagrilintide-blend'],
            ['~^Cagrisema Blend~i', 'semaglutide-cagrilintide-blend'],
            ['~^KLOW Blend~i', 'klow-blend-ghk-cu-bpc-157-tb-500-kpv'],
            ['~^MT-2 / Melanotan II\b~i', 'melanotan-ii'],
            ['~^MT-1 / Melanotan I\b~i', 'melanotan-i'],
            ['~^Mazdutide\b~i', 'mazdutide'],
            ['~^Eloralintide\b~i', 'eloralintide'],
            ['~^Thymulin\b~i', 'thymulin'],
            ['~^ACE-031\b~i', 'ace-031'],
            ['~^AHK-Cu\b~i', 'ahk-cu'],
            ['~^AICAR\b~i', 'aicar'],
            ['~^Adamax\b~i', 'adamax'],
            ['~^Cerebrolysin\b~i', 'cerebrolysin'],
            ['~^P21\b~i', 'p21'],
            ['~^PNC-27\b~i', 'pnc-27'],
        ];
        foreach (self::BIOREGULATORS as $name) {
            $rules[] = ['~^'.$name.'\b~i', strtolower($name)];
        }

        return $rules;
    }

    public function up(): void
    {
        if (! Schema::hasTable('brands') || ! Schema::hasTable('products') || ! Schema::hasTable('product_categories')) {
            return;
        }

        $brandId = DB::table('brands')->where('slug', self::BRAND_SLUG)->value('id');
        if (! $brandId) {
            return;
        }

        $uncategorizedIds = DB::table('product_categories')->where('slug', 'uncategorized')->pluck('id')->all();
        $targets = [];
        $resolve = function (string $slug) use (&$targets): ?int {
            return $targets[$slug] ??= ProductCategory::findForCompareSlug($slug)?->id;
        };

        $changed = [];
        $products = DB::table('products')->where('brand_id', $brandId)->get(['id', 'name', 'product_category_id', 'hidden']);
        foreach ($products as $product) {
            foreach ($this->rules() as [$pattern, $slug]) {
                if (preg_match($pattern, (string) $product->name) !== 1) {
                    continue;
                }

                $target = $slug === null ? null : $resolve($slug);
                if ($slug !== null && $target === null) {
                    break; // No such compare page: leave the product as it is.
                }

                $current = $product->product_category_id === null ? null : (int) $product->product_category_id;
                $wasUnfiled = $current === null || in_array($current, $uncategorizedIds, false);
                $update = [];
                if ($current !== $target) {
                    $update['product_category_id'] = $target;
                }
                if ($target !== null && (bool) $product->hidden && $wasUnfiled) {
                    $update['hidden'] = false;
                }
                if ($update !== []) {
                    $changed[$product->id] = ['product_category_id' => $current, 'hidden' => (bool) $product->hidden];
                    DB::table('products')->where('id', $product->id)->update($update + ['updated_at' => now()]);
                }
                break;
            }
        }

        $aliases = [];
        if (Schema::hasTable('category_aliases')) {
            foreach (self::ALIASES as $keyword => $slug) {
                $categoryId = $resolve($slug);
                if ($categoryId === null) {
                    continue;
                }
                $previous = DB::table('category_aliases')->where('keyword', $keyword)->value('product_category_id');
                if ($previous !== null && (int) $previous === $categoryId) {
                    continue;
                }
                $aliases[$keyword] = $previous === null ? null : (int) $previous;
                DB::table('category_aliases')->updateOrInsert(
                    ['keyword' => $keyword],
                    ['product_category_id' => $categoryId, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        if (Schema::hasTable('settings') && ($changed !== [] || $aliases !== [])) {
            DB::table('settings')->updateOrInsert(
                ['key' => self::BACKUP_KEY],
                [
                    'value' => json_encode(['products' => $changed, 'aliases' => $aliases]),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $backup = json_decode((string) DB::table('settings')->where('key', self::BACKUP_KEY)->value('value'), true);
        if (! is_array($backup)) {
            return;
        }

        foreach ($backup['products'] ?? [] as $id => $previous) {
            DB::table('products')->where('id', (int) $id)->update([
                'product_category_id' => $previous['product_category_id'],
                'hidden' => (bool) $previous['hidden'],
                'updated_at' => now(),
            ]);
        }

        foreach ($backup['aliases'] ?? [] as $keyword => $previous) {
            if ($previous === null) {
                DB::table('category_aliases')->where('keyword', $keyword)->delete();
            } else {
                DB::table('category_aliases')->where('keyword', $keyword)->update([
                    'product_category_id' => (int) $previous,
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('settings')->where('key', self::BACKUP_KEY)->delete();
    }
};
