<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductsController extends Controller
{
    public function index()
    {
        // Group products by name and count items
        $productGroups = Product::select('name')
            ->selectRaw('count(*) as total_items')
            ->selectRaw('MIN(image_url) as image_url')
            ->groupBy('name')
            ->orderBy('name')
            ->get()
            ->map(function ($group) {
                return [
                    'name' => $group->name,
                    'total_items' => $group->total_items,
                    'image' => $group->image_url ?: '/images/peptides/default.png',
                    'slug' => Str::slug($group->name),
                ];
            });
        
        return Inertia::render('Frontend/Products', [
            'productGroups' => $productGroups,
        ]);
    }
}
