<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryAlias extends Model
{
    protected $fillable = ['product_category_id', 'keyword'];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Find the best matching category for a product name.
     * Checks aliases from longest to shortest for best match.
     */
    public static function matchCategory(string $productName): ?ProductCategory
    {
        $name = strtolower(trim($productName));

        // Get all aliases ordered by keyword length (longest first for best match)
        $aliases = self::with('category')
            ->orderByRaw('LENGTH(keyword) DESC')
            ->get();

        foreach ($aliases as $alias) {
            $keyword = strtolower($alias->keyword);
            // Check if keyword appears in product name (word boundary or contained)
            if (str_contains($name, $keyword)) {
                return $alias->category;
            }
            // Also check regex pattern if keyword looks like one
            if (str_starts_with($keyword, '/') && str_ends_with($keyword, '/i')) {
                if (preg_match($keyword, $name)) {
                    return $alias->category;
                }
            }
        }

        return null;
    }
}
