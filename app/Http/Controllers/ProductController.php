<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Legacy id-first URL: /product/{id}/{slug}.
     *
     * The slug segment is ignored. Any string used to return 200 with a
     * self-canonical, so /product/1089/not-the-real-slug was an indexable
     * duplicate of the real product page. Send every variant to
     * /product/{vendorSlug}/{productSlug}/{id}.
     */
    public function show($id, $slug = null)
    {
        $product = Product::with('brand')->findOrFail($id);
        $brandSlug = $product->brand?->slug;
        $productSlug = $product->slug;

        if (!$brandSlug || !$productSlug) {
            abort(404);
        }

        return redirect()->route('product.detail', [
            'vendorSlug' => $brandSlug,
            'productSlug' => $productSlug,
            'id' => $product->id,
        ], 301);
    }
}
