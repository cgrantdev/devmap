<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function show($id, $slug = null)
    {
        $product = Product::with(['user.vendorSetting'])->findOrFail($id);
        $vendor = $product->user;
        $banner_url = $vendor && $vendor->vendorSetting && $vendor->vendorSetting->banner
            ? asset('storage/' . $vendor->vendorSetting->banner)
            : null;
        return Inertia::render('Product/Public', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => \Str::slug($product->name),
                'price' => $product->price,
                'image_url' => $product->image_url,
                'description' => $product->description,
                'product_url' => $product->product_url,
            ],
            'vendor' => [
                'name' => $vendor ? $vendor->name : '-',
                'banner_url' => $banner_url,
            ]
        ]);
    }
} 