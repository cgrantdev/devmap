<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ScrapingConfig;
use Illuminate\Http\Request;

class ScrapingConfigController extends Controller
{
    public function store(Request $request)
    {
        // Validate and save scraping config
        $data = $request->validate([
            'vendor_id' => 'required|integer',
            'products_url' => 'required|url',
            'frequency' => 'required|string',
            'selectors' => 'required|array',
        ]);

        // Save to database (example)
        $brand = Brand::findOrFail($request->vendor_id);

        $config = ScrapingConfig::create([
            'vendor_id'   => $brand->id,
            'vendor_name' => $brand->name,
            'products_url' => $request->products_url,
            'frequency'   => $request->frequency,
            'selectors'   => $request->selectors,
        ]);
        
        // Calculate next run time for new config
        $config->calculateNextRunAt();
        $config->save();

        return redirect()->back()->with('success', 'Scraping config added.');
    }

    public function update(Request $request, $id)
    {        
        $data = $request->validate([
            'vendor_id' => 'required|integer',
            'products_url' => 'required|url',
            'frequency' => 'required|string',
            'selectors' => 'required|array',
        ]);
        $config = ScrapingConfig::findOrFail($id);
        $brand = Brand::findOrFail($request->vendor_id);
        
        $frequencyChanged = $config->frequency !== $request->frequency;
        
        $config->update([
            'vendor_id'   => $brand->id,
            'vendor_name' => $brand->name,   // <-- IMPORTANT
            'products_url' => $request->products_url,
            'frequency'   => $request->frequency,
            'selectors'   => $request->selectors,
        ]);
        
        // Recalculate next run time if frequency changed or if next_run_at is null
        if ($frequencyChanged || $config->next_run_at === null) {
            $config->calculateNextRunAt();
            $config->save();
        }

        return redirect()->back()->with('success', 'Scraping config updated.');
    }

}
