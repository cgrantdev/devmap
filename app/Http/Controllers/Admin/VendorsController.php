<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class VendorsController extends Controller
{
    public function index()
    {
        $vendors = User::where('role', 'vendor')
            ->with('vendorSetting')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'email' => $vendor->email,
                    'role' => $vendor->role,
                    'created_at' => $vendor->created_at->format('Y-m-d'),
                    'settings' => $vendor->vendorSetting ? [
                        'company_name' => $vendor->vendorSetting->company_name,
                        'status' => $vendor->vendorSetting->status,
                        'logo' => $vendor->vendorSetting->logo ? asset('storage/' . $vendor->vendorSetting->logo) : null,
                    ] : null
                ];
            });

        return Inertia::render('Admin/Vendors', [
            'vendors' => $vendors
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $vendorSetting = VendorSetting::where('user_id', $id)->first();
        
        if ($vendorSetting) {
            $oldStatus = $vendorSetting->status;
            $vendorSetting->status = $vendorSetting->status === 1 ? 0 : 1;
            $vendorSetting->save();
            
            $statusText = $vendorSetting->status === 1 ? 'activated' : 'deactivated';
            
            return redirect()->back()->with('success', "Vendor has been {$statusText} successfully.");
        }
        
        return redirect()->back()->with('error', 'Vendor settings not found.');
    }

    public function create()
    {
        return Inertia::render('Admin/VendorCreate');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    if (User::where('name', $value)->where('role', 'vendor')->exists()) {
                        $fail('The vendor name has already been taken.');
                    }
                },
            ],
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'company_name' => 'nullable|string|max:255',
            'company_detail' => 'nullable|string|max:1000',
            'url' => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:50',
            'banner' => 'nullable|image|max:2048',
            'logo' => 'nullable|image|max:1024',
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => 'vendor',
        ]);
        $settings = new VendorSetting(['user_id' => $user->id]);
        // Handle banner upload
        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('vendor_banners', 'public');
            $settings->banner = $bannerPath;
        }
        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('vendor_logos', 'public');
            $settings->logo = $logoPath;
        }
        $settings->company_name = $validated['company_name'] ?? null;
        $settings->company_detail = $validated['company_detail'] ?? null;
        $settings->url = $validated['url'] ?? null;
        $settings->contact_email = $validated['contact_email'] ?? null;
        $settings->phone_number = $validated['phone_number'] ?? null;
        $settings->status = 0;
        $settings->save();
        return redirect()->route('admin.vendors')->with('success', 'Vendor created successfully.');
    }

    public function edit($id)
    {
        $vendor = User::where('role', 'vendor')
            ->with('vendorSetting')
            ->findOrFail($id);

        // Fetch products for this vendor
        $products = $vendor->products()
            ->latest()
            ->get(['id', 'name', 'price', 'image_url', 'product_url', 'discount_price', 'second_price']);

        // Prepare vendor data
        $vendorData = [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'email' => $vendor->email,
            'settings' => $vendor->vendorSetting ? [
                'company_name' => $vendor->vendorSetting->company_name,
                'company_detail' => $vendor->vendorSetting->company_detail,
                'url' => $vendor->vendorSetting->url,
                'contact_email' => $vendor->vendorSetting->contact_email,
                'phone_number' => $vendor->vendorSetting->phone_number,
                'shop_url' => $vendor->vendorSetting->shop_url,
                'banner' => $vendor->vendorSetting->banner,
                'logo' => $vendor->vendorSetting->logo,
                'banner_url' => $vendor->vendorSetting->banner ? asset('storage/' . $vendor->vendorSetting->banner) : null,
                'logo_url' => $vendor->vendorSetting->logo ? asset('storage/' . $vendor->vendorSetting->logo) : null,
            ] : null,
        ];

        return Inertia::render('Admin/VendorEdit', [
            'vendor' => $vendorData,
            'products' => $products,
        ]);
    }

    public function update(Request $request, $id)
    {
        $vendor = User::where('role', 'vendor')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $vendor->id,
            'phone_number' => 'nullable|string|max:50',
            'banner' => 'nullable|image|max:2048',
            'logo' => 'nullable|image|max:1024',
        ]);

        $vendor->update($validated);
        if ($request->filled('password')) {
            $vendor->update(['password' => bcrypt($request->input('password'))]);
        }

        $settings = $vendor->vendorSetting ?: new VendorSetting(['user_id' => $vendor->id]);

        // Handle banner upload
        if ($request->hasFile('banner')) {
            // Optionally delete old file
            if ($settings->banner) {
                \Storage::disk('public')->delete($settings->banner);
            }
            $bannerPath = $request->file('banner')->store('vendor_banners', 'public');
            $settings->banner = $bannerPath;
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Optionally delete old file
            if ($settings->logo) {
                \Storage::disk('public')->delete($settings->logo);
            }
            $logoPath = $request->file('logo')->store('vendor_logos', 'public');
            $settings->logo = $logoPath;
        }

        $settings->company_name = $request->input('company_name', $settings->company_name);
        $settings->company_detail = $request->input('company_detail', $settings->company_detail);
        $settings->url = $request->input('url', $settings->url);
        $settings->contact_email = $request->input('contact_email', $settings->contact_email);
        $settings->phone_number = $request->input('phone_number', $settings->phone_number);
        $settings->shop_url = $request->input('shop_url', $settings->shop_url);
        $settings->save();

        // Refresh the vendor data to get updated URLs
        $vendor->refresh();
        $settings->refresh();

        return redirect()->route('admin.vendors.edit', $vendor->id)->with('success', 'Vendor updated successfully.');
    }

    public function destroy($id)
    {
        $vendor = User::where('role', 'vendor')->findOrFail($id);
        // Delete vendor settings and files
        $settings = $vendor->vendorSetting;
        if ($settings) {
            // Delete banner file
            if ($settings->banner) {
                \Storage::disk('public')->delete($settings->banner);
            }
            // Delete logo file
            if ($settings->logo) {
                \Storage::disk('public')->delete($settings->logo);
            }
            $settings->delete();
        }
        // Delete all products for this vendor
        if ($vendor->products) {
            foreach ($vendor->products as $product) {
                $product->delete();
            }
        }
        $vendor->delete();
        return redirect()->route('admin.vendors')->with('success', 'Vendor deleted successfully.');
    }

    public function products($id)
    {
        $vendor = User::where('role', 'vendor')->findOrFail($id);
        $products = $vendor->products()->latest()->get();
        return Inertia::render('Admin/VendorProducts', [
            'vendor' => $vendor,
            'products' => $products
        ]);
    }

    public function importProductsFromFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:xml|max:10240',
        ]);
        $vendor = User::where('role', 'vendor')->findOrFail($id);
        $file = $request->file('file');
        try {
            $xmlContent = file_get_contents($file->getPathname());
            $xml = new \SimpleXMLElement($xmlContent);
            $importedCount = 0;
            $skippedCount = 0;
            if (isset($xml->product)) {
                foreach ($xml->product as $productData) {
                    $productUrl = (string) $productData->url;
                    if ($productUrl && $vendor->products()->where('product_url', $productUrl)->exists()) {
                        $skippedCount++;
                        continue;
                    }
                    $vendor->products()->create([
                        'name' => (string) $productData->name,
                        'price' => preg_replace('/[^0-9.]/', '', (string) $productData->price) ?: '0.00',
                        'image_url' => (string) $productData->image,
                        'product_url' => $productUrl,
                    ]);
                    $importedCount++;
                }
            }
            $message = "Successfully imported {$importedCount} products.";
            if ($skippedCount > 0) {
                $message .= " Skipped {$skippedCount} duplicate products.";
            }
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error parsing XML file: ' . $e->getMessage());
        }
    }

    public function importProductsFromUrl(Request $request, $id)
    {
        $request->validate([
            'url' => 'required|url',
        ]);
        
        $vendor = User::where('role', 'vendor')->findOrFail($id);
        
        try {
            $xmlContent = file_get_contents($request->input('url'));
            if ($xmlContent === false) {
                return redirect()->back()->with('error', 'Could not fetch XML from the provided URL.');
            }
            
            $xml = new \SimpleXMLElement($xmlContent);
            $importedCount = 0;
            $skippedCount = 0;
            
            if (isset($xml->product)) {
                foreach ($xml->product as $productData) {
                    $productUrl = (string) $productData->url;
                    if ($productUrl && $vendor->products()->where('product_url', $productUrl)->exists()) {
                        $skippedCount++;
                        continue;
                    }
                    $vendor->products()->create([
                        'name' => (string) $productData->name,
                        'price' => preg_replace('/[^0-9.]/', '', (string) $productData->price) ?: '0.00',
                        'image_url' => (string) $productData->image,
                        'product_url' => $productUrl,
                    ]);
                    $importedCount++;
                }
            }
            
            $message = "Successfully imported {$importedCount} products from URL.";
            if ($skippedCount > 0) {
                $message .= " Skipped {$skippedCount} duplicate products.";
            }
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error parsing XML from URL: ' . $e->getMessage());
        }
    }

    public function deleteProduct($vendorId, $productId)
    {
        $vendor = User::where('role', 'vendor')->findOrFail($vendorId);
        $product = $vendor->products()->findOrFail($productId);
        $product->delete();
        return redirect()->back()->with('success', 'Product deleted successfully.');
    }

    public function adminProducts(Request $request)
    {
        $products = \App\Models\Product::with('user')
            ->latest()
            ->paginate(20)
            ->through(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => \Str::slug($product->name),
                    'price' => $product->price,
                    'image_url' => $product->image_url,
                    'vendor_name' => $product->user ? $product->user->name : '-',
                ];
            });
        return \Inertia\Inertia::render('Admin/Products', [
            'products' => $products
        ]);
    }

    /**
     * Import products from the vendor's shop_url (WooCommerce shop page scraping)
     */
    public function importFromShopUrl(Request $request, $id)
    {
        $vendor = User::where('role', 'vendor')->findOrFail($id);
        $settings = $vendor->vendorSetting;
        $shopUrl = $settings ? $settings->shop_url : null;
        $settings_id = $settings ? $settings->id : null;
        if (!$shopUrl) {
            return redirect()->back()->with('error', 'Shop URL is not set for this vendor.');
        }
        // try {
            if (strpos($shopUrl, 'trueaminos.com/category/peptides') !== false) {
                $products = $this->runPythonScraper($shopUrl, 'script2.py');
                $api_route = 'python';
            } else {
                $data = $this->fetchProducts($shopUrl);
                $products = $data['products'];
                $api_route = $data['api_route'];
            }
            
            $importedCount = 0;
            $skippedCount = 0;
            $updatedCount = 0;
            foreach ($products as $product) {
                $existing = $vendor->products()->where('product_url', $product['product_url'])->first();
                if ($existing) {                   
                    // Update price, discount_price, image_url, and name
                    $existing->update([
                        'price' => $product['price'],
                        'discount_price' => $product['discount_price'],
                        'second_price' => $product['second_price'],
                        'name' => $product['name'],
                        'image_url' => $product['image_url'],
                    ]);
                    $updatedCount++;
                    continue;
                }
                $vendor->products()->create([
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'discount_price' => $product['discount_price'],
                    'second_price' => $product['second_price'],
                    'image_url' => $product['image_url'],
                    'product_url' => $product['product_url'],
                ]);
                $importedCount++;
            }
            $message = "Imported {$importedCount} new products from shop URL.";
            if ($updatedCount > 0) {
                $message .= " Updated {$updatedCount} products.";
            }
            if ($skippedCount > 0) {
                $message .= " Skipped {$skippedCount} unchanged products.";
            }
            VendorSetting::where('id', $settings_id)->update(['api_route' => $api_route]);
            return redirect()->back()->with('success', $message);
        // } catch (\Exception $e) {
        //     return redirect()->back()->with('error', 'Error scraping shop URL: ' . $e->getMessage());
        // }
    }

    private function fetchProducts($shopUrl)
    {
        $client = new \GuzzleHttp\Client(['timeout' => 30, 'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
        ]]);
        $products = [];
        $page = 1;
        $perPage = 100;
        $data = [];
        do {
            $shopUrl = rtrim($shopUrl, '/');
            $api_route = 'normal';
            $apiUrl = "$shopUrl/wp-json/wc/store/v1/products?per_page=$perPage&page=$page";
            $response = null;
            
            try {
                $response = $client->get($apiUrl);
            } catch (\Exception $e) {
                \Log::info("API Request Failed: " . $e->getMessage());
                $statusCode = $e->getCode();
                \Log::info('statuscode_'.$statusCode);

                if ($statusCode == 403) {
                    \Log::info('403 Forbidden - Using proxy');
                    $proxytoken = 'e3980c24459441efbc4ca2d232d91e7663bfa3d6897';
                    $encodedUrl = urlencode($apiUrl);
                    $url = "https://api.scrape.do/?token=$proxytoken&url=$encodedUrl";
                    \Log::info($url);
                    try {
                        $response = $client->get($url);
                        $api_route = 'proxy';
                    } catch (\Exception $e) {
                        $statusCode = $e->getCode();
                        \Log::info('proxystatuscode_'.$statusCode);
                        \Log::info("Proxy request also failed: " . $e->getMessage());
                    }
                }
            }

            if(!$response) {
                return [
                    'products' => [],
                    'api_route' => 'nothing',
                ];
            }
          
            $data = json_decode((string)$response->getBody(), true);
            if (!is_array($data) || empty($data)) {
                break;
            }
            
            foreach ($data as $item) {
                // Filter: only include products with a category slug 'peptides'
                // $hasPeptidesCategory = false;
                // if (empty($item['categories'])) {
                //     $hasPeptidesCategory = true;
                // }
                // if (isset($item['categories']) && is_array($item['categories'])) {
                //     foreach ($item['categories'] as $cat) {
                //         if (isset($cat['slug']) && strpos($cat['slug'], 'peptide') !== false) {
                //             $hasPeptidesCategory = true;
                //             break;
                //         }
                //     }
                // }
                // if (!$hasPeptidesCategory) {
                //     continue;
                // }
                $price = isset($item['prices']['regular_price']) ? ((float)$item['prices']['regular_price'] / 100) : null;
                $discount_price = (isset($item['on_sale']) && $item['on_sale'] && isset($item['prices']['sale_price']))
                    ? ((float)$item['prices']['sale_price'] / 100)
                    : null;
                $image_urls = [];
                if (!empty($item['images'])) {
                    foreach ($item['images'] as $image) {
                        $image_urls[] = $image['src'];
                    }
                }
                $products[] = [
                    'name' => $item['name'] ?? null,
                    'price' => $price,
                    'discount_price' => $discount_price,
                    'second_price' => null,
                    'image_url' => isset($image_urls[0])?$image_urls[0]:null,
                    'product_url' => $item['permalink'] ?? null,
                ];
            }
            $page++;
        } while (count($data) === $perPage && $page <= 10); // safety limit
        \Log::info(count($products));
        return [
            'products' => $products,
            'api_route' => $api_route,
        ];
    }


    public function runPythonScraper($shopUrl, $file_name)
    {        

        $pythonBin = dirname(base_path()) . '/venv/bin/python3.12';
        // $pythonScript = dirname(base_path()) . '/pyscripts/test.py';

        $pythonScript = base_path() . '/pyscripts/' . $file_name;

        $escapedUrl = escapeshellarg($shopUrl);

        $command = 'sudo -u devuser ' . $pythonBin . ' ' . escapeshellarg($pythonScript) . ' ' . $escapedUrl . ' 2>&1';

        \Log::info($command);

        $output = shell_exec($command);
        // \Log::info($output);
        $data = json_decode($output, true);
        if (isset($data['error'])) {
            return [];
        }
        return $data['products'];
    } 
} 