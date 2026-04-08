<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ScrapedProduct;
use App\Services\IngestionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StagedProductsController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'all'])],
            'brand_id' => 'nullable|integer',
            'source_type' => 'nullable|string',
            'q' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $status = $validated['status'] ?? 'pending';
        $query = ScrapedProduct::query()
            ->with(['brand:id,name', 'product:id,slug', 'scrapingConfig:id,type,vendor_name']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if (!empty($validated['brand_id'])) {
            $query->where('brand_id', $validated['brand_id']);
        }
        if (!empty($validated['source_type'])) {
            $query->where('source_type', $validated['source_type']);
        }
        if (!empty($validated['q'])) {
            $q = $validated['q'];
            $query->where('name', 'like', "%{$q}%");
        }

        $staged = $query->latest('last_scraped_at')
            ->paginate($validated['per_page'] ?? 25)
            ->withQueryString()
            ->through(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'brand' => $row->brand?->name,
                'brand_id' => $row->brand_id,
                'source_type' => $row->source_type,
                'status' => $row->status,
                'price' => $row->price,
                'discount_price' => $row->discount_price,
                'previous_price' => $row->previous_price,
                'price_changed_at' => $row->price_changed_at?->toDateTimeString(),
                'image_url' => $row->image_url,
                'source_url' => $row->source_url,
                'external_id' => $row->external_id,
                'stock_status' => $row->stock_status,
                'last_scraped_at' => $row->last_scraped_at?->toDateTimeString(),
                'already_promoted' => (bool) $row->product_id,
                'product_slug' => $row->product?->slug,
            ]);

        $counts = [
            'pending' => ScrapedProduct::pending()->count(),
            'approved' => ScrapedProduct::approved()->count(),
            'rejected' => ScrapedProduct::where('status', ScrapedProduct::STATUS_REJECTED)->count(),
        ];

        $brands = Brand::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/StagedProducts', [
            'staged' => $staged,
            'counts' => $counts,
            'brands' => $brands,
            'filters' => [
                'status' => $status,
                'brand_id' => $validated['brand_id'] ?? null,
                'source_type' => $validated['source_type'] ?? null,
                'q' => $validated['q'] ?? '',
            ],
        ]);
    }

    public function promote(ScrapedProduct $stagedProduct, IngestionService $ingestion)
    {
        $product = $ingestion->promote($stagedProduct);
        return back()->with('success', "Promoted to product #{$product->id}");
    }

    public function reject(Request $request, ScrapedProduct $stagedProduct, IngestionService $ingestion)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        $ingestion->reject($stagedProduct, $validated['reason'] ?? null);
        return back()->with('success', 'Staged product rejected.');
    }

    public function bulkPromote(Request $request, IngestionService $ingestion)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer',
        ]);

        $count = 0;
        foreach (ScrapedProduct::whereIn('id', $validated['ids'])->get() as $row) {
            $ingestion->promote($row);
            $count++;
        }
        return back()->with('success', "Promoted {$count} products.");
    }

    public function bulkReject(Request $request, IngestionService $ingestion)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer',
        ]);

        $count = 0;
        foreach (ScrapedProduct::whereIn('id', $validated['ids'])->get() as $row) {
            $ingestion->reject($row);
            $count++;
        }
        return back()->with('success', "Rejected {$count} products.");
    }
}
