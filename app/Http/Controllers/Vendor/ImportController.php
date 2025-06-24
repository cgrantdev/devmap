<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use SimpleXMLElement;
use App\Models\Product;

class ImportController extends Controller
{
    private function extractPrice($priceString)
    {
        // Remove currency symbols and extract numeric value
        $price = preg_replace('/[^0-9.]/', '', $priceString);
        return $price ?: '0.00';
    }

    public function index()
    {
        $user = Auth::user();
        $products = $user->products()->latest()->get();
        
        return Inertia::render('Vendor/Import', [
            'products' => $products
        ]);
    }

    public function importFromFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xml|max:10240', // 10MB max
        ]);

        $user = Auth::user();
        $file = $request->file('file');
        
        try {
            $xmlContent = file_get_contents($file->getPathname());
            $xml = new SimpleXMLElement($xmlContent);
            
            $importedCount = 0;
            
            if (isset($xml->product)) {
                foreach ($xml->product as $productData) {
                    $product = Product::create([
                        'user_id' => $user->id,
                        'name' => (string) $productData->name,
                        'price' => $this->extractPrice((string) $productData->price),
                        'image_url' => (string) $productData->image,
                        'product_url' => (string) $productData->url,
                    ]);
                    
                    $importedCount++;
                }
            }
            
            return redirect()->back()->with('success', "Successfully imported {$importedCount} products.");
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error parsing XML file: ' . $e->getMessage());
        }
    }

    public function importFromUrl(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $user = Auth::user();
        
        try {
            $response = Http::timeout(30)->get($request->url);
            
            if (!$response->successful()) {
                return redirect()->back()->with('error', 'Failed to fetch XML from URL.');
            }
            
            $xmlContent = $response->body();
            $xml = new SimpleXMLElement($xmlContent);
            
            $importedCount = 0;
            
            if (isset($xml->product)) {
                foreach ($xml->product as $productData) {
                    $product = Product::create([
                        'user_id' => $user->id,
                        'name' => (string) $productData->name,
                        'price' => $this->extractPrice((string) $productData->price),
                        'image_url' => (string) $productData->image,
                        'product_url' => (string) $productData->url,
                    ]);
                    
                    $importedCount++;
                }
            }
            
            return redirect()->back()->with('success', "Successfully imported {$importedCount} products from URL.");
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing from URL: ' . $e->getMessage());
        }
    }

    public function deleteProduct(Product $product)
    {
        $user = Auth::user();
        
        // Check if the product belongs to the user
        if ($product->user_id !== $user->id) {
            return redirect()->back()->with('error', 'Product not found.');
        }
        
        // Delete the product
        $product->delete();
        
        return redirect()->back()->with('success', 'Product deleted successfully.');
    }
} 