<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Brand;
use App\Models\VendorSetting;
use App\Models\ProductCategory;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Helpers\ImageHelper;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class VendorsController extends Controller
{
    public function index()
    {
        $vendors = Brand::with(['vendorSetting.location'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'brand_id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'email' => $brand->vendorSetting?->contact_email ?? null,
                    'location' => $brand->vendorSetting && $brand->vendorSetting->location ? $brand->vendorSetting->location->name : null,
                    'created_at' => $brand->created_at->format('n/j/Y'), // Format: 12/3/2025
                    'is_active' => $brand->is_active,
                    'rating_average' => $brand->rating_average ?? 0,
                    'rating_count' => $brand->rating_count ?? 0,
                    'settings' => $brand->vendorSetting ? [
                        'status' => $brand->vendorSetting->status,
                        'logo' => $brand->vendorSetting->logo ? asset('storage/' . $brand->vendorSetting->logo) : null,
                        'logo_url' => $brand->vendorSetting->logo ? asset('storage/' . $brand->vendorSetting->logo) : null,
                        'banner_url' => $brand->vendorSetting->banner ? asset('storage/' . $brand->vendorSetting->banner) : null,
                        'shop_url' => $brand->vendorSetting->shop_url ?? null, // shop_url is the website
                        'location_id' => $brand->vendorSetting->location_id ?? null,
                        'description' => $brand->vendorSetting->description ?? null,
                        'contact_email' => $brand->vendorSetting->contact_email ?? null,
                        'phone_number' => $brand->vendorSetting->phone_number ?? null,
                        'founded_year' => $brand->vendorSetting->founded_year ?? null,
                        'coupon_code' => $brand->vendorSetting->coupon_code ?? null,
                        'shipping_info' => $brand->vendorSetting->shipping_info ?? null,
                        'return_policy' => $brand->vendorSetting->return_policy ?? null,
                        'business_hours' => $brand->vendorSetting->business_hours ?? null,
                        'banner_image_url' => $brand->vendorSetting->banner_image_url ?? null,
                        'top_vendor' => $brand->vendorSetting->top_vendor ?? false,
                        'featured' => $brand->vendorSetting->featured ?? false,
                        'is_partner' => $brand->vendorSetting->is_partner ?? false,
                    ] : null
                ];
            });

        $locations = Location::orderBy('name')->get();

        return Inertia::render('Admin/Vendors', [
            'vendors' => $vendors,
            'locations' => $locations
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);
        
        // Toggle brand is_active status
        $brand->is_active = !$brand->is_active;
        $brand->save();
        
        // Also update vendor setting status if it exists
        if ($brand->vendorSetting) {
            $brand->vendorSetting->status = $brand->is_active ? 1 : 0;
            $brand->vendorSetting->save();
        }
        
        $statusText = $brand->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()->with('success', "Vendor has been {$statusText} successfully.");
    }

    public function create()
    {
        $locations = Location::orderBy('name')->get();

        return Inertia::render('Admin/VendorEdit', [
            'vendor' => null,
            'products' => [],
            'locations' => $locations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (Brand::where('name', $value)->exists()) {
                        $fail('The vendor name has already been taken.');
                    }
                },
            ],
            'shop_url' => 'required|url|max:255',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'contact_email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:50',
            'location' => 'required|string|max:255',
            'founded_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'coupon_code' => 'nullable|string|max:50',
            'shipping_info' => 'nullable|string|max:2000',
            'return_policy' => 'nullable|string|max:2000',
            'business_hours' => 'nullable|string|max:255',
            'banner_image_url' => 'nullable|url|max:500',
            'top_vendor' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
            'is_partner' => 'nullable|boolean',
            'banner' => 'nullable|image|max:2048',
            'logo' => 'nullable|mimes:jpeg,jpg,png,gif,webp,svg|max:1024',
        ]);

        // Create Brand (vendor) - no user account
        $brand = Brand::create([
            'name' => $validated['name'],
            'is_active' => true, // Active by default
        ]);

        // Create VendorSetting
        $settings = new VendorSetting(['brand_id' => $brand->id]);
        
        // Handle banner upload and convert to WebP
        if ($request->hasFile('banner')) {
            $bannerFilename = ImageHelper::convertToWebP($request->file('banner'), 'vendor_banners');
            $settings->banner = 'vendor_banners/' . $bannerFilename;
        }
        
        // Handle logo upload - convert to WebP if not SVG, otherwise save as-is
        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $extension = strtolower($logoFile->getClientOriginalExtension());
            $mimeType = $logoFile->getMimeType();
            
            // Check if it's SVG by extension or MIME type
            if ($extension === 'svg' || $mimeType === 'image/svg+xml') {
                // Save SVG as-is
                $logoFilename = Str::random(40) . '.svg';
                $logoFile->storeAs('vendor_logos', $logoFilename, 'public');
                $settings->logo = 'vendor_logos/' . $logoFilename;
            } else {
                // Convert other formats to WebP
                $logoFilename = ImageHelper::convertToWebP($logoFile, 'vendor_logos');
                $settings->logo = 'vendor_logos/' . $logoFilename;
            }
        }
        
        $settings->description = $validated['description'] ?? null;
        $settings->shop_url = $validated['shop_url'] ?? null; // shop_url is the website
        // Save email to contact_email field
        $settings->contact_email = $validated['email'] ?? $validated['contact_email'] ?? null;
        $settings->phone_number = $validated['phone_number'] ?? null;
        
        // Find or create location by name
        $locationName = trim($validated['location']);
        $location = Location::firstOrCreate(
            ['name' => $locationName],
            ['name' => $locationName]
        );
        $settings->location_id = $location->id;
        
        $settings->founded_year = $validated['founded_year'] ?? null;
        $settings->coupon_code = $validated['coupon_code'] ?? null;
        $settings->shipping_info = $validated['shipping_info'] ?? null;
        $settings->return_policy = $validated['return_policy'] ?? null;
        $settings->business_hours = $validated['business_hours'] ?? null;
        $settings->banner_image_url = $validated['banner_image_url'] ?? null;
        $settings->top_vendor = $validated['top_vendor'] ?? false;
        $settings->featured = $validated['featured'] ?? false;
        $settings->is_partner = $validated['is_partner'] ?? false;
        $settings->status = 1; // Active by default
        $settings->save();

        return redirect()->route('admin.vendors')->with('success', 'Vendor created successfully.');
    }

    public function edit($id)
    {
        $brand = Brand::with('vendorSetting')->findOrFail($id);

        // Fetch products for this vendor (via brand)
        $products = $brand->products()
            ->latest()
            ->get(['id', 'name', 'price', 'image_url', 'product_url', 'discount_price', 'second_price']);

        // Prepare vendor data
        $vendorData = [
            'id' => $brand->id,
            'brand_id' => $brand->id,
            'name' => $brand->name,
            'email' => $brand->vendorSetting->contact_email ?? null,
            'is_active' => $brand->is_active,
            'settings' => $brand->vendorSetting ? [
                'description' => $brand->vendorSetting->description,
                'shop_url' => $brand->vendorSetting->shop_url, // shop_url is the website
                'contact_email' => $brand->vendorSetting->contact_email,
                'phone_number' => $brand->vendorSetting->phone_number,
                'location_id' => $brand->vendorSetting->location_id,
                'founded_year' => $brand->vendorSetting->founded_year,
                'coupon_code' => $brand->vendorSetting->coupon_code,
                'shipping_info' => $brand->vendorSetting->shipping_info,
                'return_policy' => $brand->vendorSetting->return_policy,
                'business_hours' => $brand->vendorSetting->business_hours,
                'banner_image_url' => $brand->vendorSetting->banner_image_url,
                'top_vendor' => $brand->vendorSetting->top_vendor ?? false,
                'featured' => $brand->vendorSetting->featured ?? false,
                'is_partner' => $brand->vendorSetting->is_partner ?? false,
                'banner' => $brand->vendorSetting->banner,
                'logo' => $brand->vendorSetting->logo,
                'banner_url' => $brand->vendorSetting->banner ? asset('storage/' . $brand->vendorSetting->banner) : null,
                'logo_url' => $brand->vendorSetting->logo ? asset('storage/' . $brand->vendorSetting->logo) : null,
            ] : null,
        ];

        $locations = Location::orderBy('name')->get();

        return Inertia::render('Admin/VendorEdit', [
            'vendor' => $vendorData,
            'products' => $products,
            'locations' => $locations,
        ]);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shop_url' => 'nullable|url|max:255', // shop_url is the website
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'contact_email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'founded_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'coupon_code' => 'nullable|string|max:50',
            'shipping_info' => 'nullable|string|max:2000',
            'return_policy' => 'nullable|string|max:2000',
            'business_hours' => 'nullable|string|max:255',
            'banner_image_url' => 'nullable|url|max:500',
            'top_vendor' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
            'is_partner' => 'nullable|boolean',
            'banner' => 'nullable|image|max:2048',
            'logo' => 'nullable|mimes:jpeg,jpg,png,gif,webp,svg|max:1024',
            'is_active' => 'nullable|boolean',
        ]);

        // Update brand name and is_active
        $brand->update([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? $brand->is_active,
        ]);

        $settings = $brand->vendorSetting ?: new VendorSetting(['brand_id' => $brand->id]);

        // Handle banner upload and convert to WebP
        if ($request->hasFile('banner')) {
            // Delete old banner if exists
            if ($settings->banner) {
                ImageHelper::deleteImage(basename($settings->banner), 'vendor_banners');
            }
            $bannerFilename = ImageHelper::convertToWebP($request->file('banner'), 'vendor_banners');
            $settings->banner = 'vendor_banners/' . $bannerFilename;
        }

        // Handle logo upload - convert to WebP if not SVG, otherwise save as-is
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($settings->logo) {
                ImageHelper::deleteImage(basename($settings->logo), 'vendor_logos');
            }
            $logoFile = $request->file('logo');
            $extension = strtolower($logoFile->getClientOriginalExtension());
            $mimeType = $logoFile->getMimeType();
            
            // Check if it's SVG by extension or MIME type
            if ($extension === 'svg' || $mimeType === 'image/svg+xml') {
                // Save SVG as-is
                $logoFilename = Str::random(40) . '.svg';
                $logoFile->storeAs('vendor_logos', $logoFilename, 'public');
                $settings->logo = 'vendor_logos/' . $logoFilename;
            } else {
                // Convert other formats to WebP
                $logoFilename = ImageHelper::convertToWebP($logoFile, 'vendor_logos');
                $settings->logo = 'vendor_logos/' . $logoFilename;
            }
        }

        $settings->description = $validated['description'] ?? $settings->description;
        $settings->shop_url = $validated['shop_url'] ?? $settings->shop_url; // shop_url is the website
        // Save email to contact_email field (use email if provided, otherwise contact_email, otherwise keep existing)
        if (isset($validated['email'])) {
            $settings->contact_email = $validated['email'];
        } elseif (isset($validated['contact_email'])) {
            $settings->contact_email = $validated['contact_email'];
        }
        $settings->phone_number = $validated['phone_number'] ?? $settings->phone_number;
        
        // Find or create location by name if provided
        if (!empty($validated['location'])) {
            $locationName = trim($validated['location']);
            $location = Location::firstOrCreate(
                ['name' => $locationName],
                ['name' => $locationName]
            );
            $settings->location_id = $location->id;
        }
        
        $settings->founded_year = $validated['founded_year'] ?? $settings->founded_year;
        $settings->coupon_code = $validated['coupon_code'] ?? $settings->coupon_code;
        $settings->shipping_info = $validated['shipping_info'] ?? $settings->shipping_info;
        $settings->return_policy = $validated['return_policy'] ?? $settings->return_policy;
        $settings->business_hours = $validated['business_hours'] ?? $settings->business_hours;
        $settings->banner_image_url = $validated['banner_image_url'] ?? $settings->banner_image_url;
        $settings->top_vendor = $validated['top_vendor'] ?? $settings->top_vendor ?? false;
        $settings->featured = $validated['featured'] ?? $settings->featured ?? false;
        $settings->is_partner = $validated['is_partner'] ?? $settings->is_partner ?? false;
        $settings->save();

        return redirect()->route('admin.vendors')->with('success', 'Vendor updated successfully.');
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        
        // Delete vendor settings and files
        if ($brand->vendorSetting) {
            $settings = $brand->vendorSetting;
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
        
        // Delete all products for this brand
        $brand->products()->delete();
        
        // Delete the brand
        $brand->delete();
        
        return redirect()->route('admin.vendors')->with('success', 'Vendor deleted successfully.');
    }

    public function products($id)
    {
        $brand = Brand::findOrFail($id);
        $products = $brand->products()->latest()->get();
        return Inertia::render('Admin/VendorProducts', [
            'vendor' => $brand,
            'products' => $products
        ]);
    }

    public function importProductsFromFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:xml|max:10240',
        ]);
        $brand = Brand::findOrFail($id);
        $file = $request->file('file');
        try {
            $xmlContent = file_get_contents($file->getPathname());
            $xml = new \SimpleXMLElement($xmlContent);
            $importedCount = 0;
            $skippedCount = 0;
            if (isset($xml->product)) {
                foreach ($xml->product as $productData) {
                    $productUrl = (string) $productData->url;
                    if ($productUrl && $brand->products()->where('product_url', $productUrl)->exists()) {
                        $skippedCount++;
                        continue;
                    }
                    $brand->products()->create([
                        'name' => (string) $productData->name,
                        'price' => preg_replace('/[^0-9.]/', '', (string) $productData->price) ?: '0.00',
                        'image_url' => (string) $productData->image,
                        'product_url' => $productUrl,
                        'brand_id' => $brand->id,
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
        
        $brand = Brand::findOrFail($id);
        
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
                    if ($productUrl && $brand->products()->where('product_url', $productUrl)->exists()) {
                        $skippedCount++;
                        continue;
                    }
                    $brand->products()->create([
                        'name' => (string) $productData->name,
                        'price' => preg_replace('/[^0-9.]/', '', (string) $productData->price) ?: '0.00',
                        'image_url' => (string) $productData->image,
                        'product_url' => $productUrl,
                        'brand_id' => $brand->id,
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
        $brand = Brand::findOrFail($vendorId);
        $product = $brand->products()->findOrFail($productId);
        $product->delete();
        return redirect()->back()->with('success', 'Product deleted successfully.');
    }

    public function adminProducts(Request $request)
    {
        $query = \App\Models\Product::with(['brand', 'category']);
        
        // Filter by brand
        if ($request->has('brand') && $request->brand && $request->brand !== 'all') {
            $query->where('brand_id', $request->brand);
        }
        
        // Filter by category
        if ($request->has('category') && $request->category && $request->category !== 'all') {
            $query->where('product_category_id', $request->category);
        }
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")                  
                  ->orWhereHas('brand', function ($brandQuery) use ($search) {
                      $brandQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('category', function ($categoryQuery) use ($search) {
                      $categoryQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', '');
        $sortType = $request->get('sort_type', 'desc');
        
        // Validate sortType
        $sortType = strtolower($sortType) === 'asc' ? 'asc' : 'desc';
        
        // Handle vendor_name sorting (requires join)
        if ($sortBy === 'vendor_name') {
            $query->join('brands', 'products.brand_id', '=', 'brands.id')
                  ->orderBy('brands.name', $sortType)
                  ->select('products.*')
                  ->distinct(); // Prevent duplicate counting
        } else {
            // Validate sortBy - if empty or invalid, default to 'id'
            $allowedSortColumns = ['id', 'name', 'price', 'created_at', 'updated_at'];
            if (empty($sortBy) || !in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'id';
            }
            $query->orderBy($sortBy, $sortType);
        }
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $products = $query->paginate($perPage)
            ->through(function ($product) {
                // If discount_price exists, price is original and discount_price is sale price
                // Frontend expects: price = sale price, original_price = original price
                $salePrice = $product->discount_price ? $product->discount_price : $product->price;
                $originalPrice = $product->discount_price ? $product->price : null;
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => \Str::slug($product->name),
                    'price' => $salePrice,
                    'original_price' => $originalPrice,
                    'image_url' => $product->image_url,
                    'hidden' => (bool) ($product->hidden ?? false),
                    'featured' => (bool) ($product->featured ?? false),
                    'lab_tested' => (bool) ($product->lab_tested ?? false),
                    'first_timer_deals' => (bool) ($product->first_timer_deals ?? false),
                    'purity' => $product->purity,
                    'brand_id' => $product->brand_id,
                    'vendor_id' => $product->brand_id,
                    'vendor_name' => $product->brand ? $product->brand->name : '-',
                    'brand_name' => $product->brand ? $product->brand->name : '-',
                    'product_category_id' => $product->product_category_id,
                    'category_id' => $product->product_category_id,
                    'category_name' => $product->category ? $product->category->name : '-',
                    'rating' => round($product->rating_average ?? 0, 1),
                    'review_count' => $product->rating_count ?? 0,
                    'size_mg' => $product->size_mg,
                    'dosage' => $product->size_mg,
                ];
            });
        // Get brands and categories for filters
        $brands = Brand::orderBy('name')
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                ];
            });

        $categories = \App\Models\ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            });

        return \Inertia\Inertia::render('Admin/Products', [
            'products' => $products,
            'brands' => $brands,
            'categories' => $categories
        ]);
    }

    /**
     * Import products from the vendor's shop_url (WooCommerce shop page scraping)
     */
    public function importFromShopUrl(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);
        $settings = $brand->vendorSetting;
        $shopUrl = $settings ? $settings->shop_url : null;
        $settings_id = $settings ? $settings->id : null;
        if (!$shopUrl) {
            return redirect()->back()->with('error', 'Shop URL is not set for this vendor.');
        }
        try {
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

            DB::beginTransaction();

            // dd($products);
            // return;

            foreach ($products as $productData) {
                $existing = $brand->products()->where('product_url', $productData['product_url'])->first();                
                
                // Map stock availability
                $availability = 'in_stock';
                if (isset($productData['is_in_stock']) && !$productData['is_in_stock']) {
                    $availability = 'out_of_stock';
                } elseif (isset($productData['is_on_backorder']) && $productData['is_on_backorder']) {
                    $availability = 'pre_order';
                }
               
                $productFields = [
                    'name' => $productData['name'],
                    'description' => $productData['description'] ?? null,
                    'price' => $productData['price'],
                    'discount_price' => $productData['discount_price'],
                    'second_price' => $productData['second_price'],
                    'size_mg' => $productData['size_mg'] ?? null,
                    'availability' => $availability,
                    'status' => 'active',
                    'product_url' => $productData['product_url'],
                    'brand_id' => $brand->id,
                ];
                
                // Use original image URL
                if (!empty($productData['image_url'])) {
                    $productFields['image_url'] = $productData['image_url'];
                }
                
                if ($existing) {
                    // Don't update the name for existing products
                    unset($productFields['name']);
                    $existing->update($productFields);
                    $updatedCount++;
                } else {
                    // Find similar category or create new one
                    $category = null;
                    if (!empty($productData['category_name'])) {
                        // First, try to find a similar category
                        $categoryName = $productData['category_name'];
                        $categoryNameLower = strtolower($categoryName);
                        
                        // First, check if a category with this exact slug already exists (check ALL categories, not just active)
                        $baseSlug = Str::slug($categoryName);
                        $existingCategory = ProductCategory::where('slug', $baseSlug)->first();
                        
                        if ($existingCategory) {
                            // Use the existing category
                            $category = $existingCategory;
                        } else {
                            // Search for similar categories by name (case-insensitive, partial match)
                            $similarCategory = ProductCategory::where(function ($query) use ($categoryNameLower) {
                                // Exact match (case-insensitive)
                                $query->whereRaw('LOWER(name) = ?', [$categoryNameLower])
                                    // Contains the category name
                                    ->orWhereRaw('LOWER(name) LIKE ?', ['%' . $categoryNameLower . '%'])
                                    // Category name contains the other name
                                    ->orWhereRaw('? LIKE CONCAT("%", LOWER(name), "%")', [$categoryNameLower]);
                            })
                            ->orderBy('name')
                            ->first();
                            
                            if ($similarCategory) {
                                // Use the similar category found
                                $category = $similarCategory;
                            } else {
                                // No similar category found, create a new one
                                // Let the model's boot method handle unique slug generation
                                $category = ProductCategory::create([
                                    'name' => $categoryName,
                                    'is_active' => true,
                                ]);
                            }
                        }
                    }                   

                    if ($category) {
                        $productFields['product_category_id'] = $category->id;
                    }

                    $product = $brand->products()->create($productFields);
                    $importedCount++;
                    
                    // Log activity for first product imported (to avoid spam)
                    if ($importedCount === 1) {
                        ActivityLogger::productAdded($product->name, $brand->name, $product->id);
                    }
                }
            }
            $message = "Imported {$importedCount} new products from shop URL.";
            if ($updatedCount > 0) {
                $message .= " Updated {$updatedCount} products.";
            }
            if ($skippedCount > 0) {
                $message .= " Skipped {$skippedCount} unchanged products.";
            }
            VendorSetting::where('id', $settings_id)->update(['api_route' => $api_route]);
            DB::commit();
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Product import error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Error importing products: ' . $e->getMessage());
        }
    }

    private function fetchProducts($shopUrl)
    {
        $client = new \GuzzleHttp\Client(['timeout' => 60, 'headers' => [
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
            \Log::info('Fetching products from API', ['url' => $apiUrl, 'route' => 'normal']);
            try {
                $response = $client->get($apiUrl);
            } catch (\Exception $e) {
                \Log::info('API Request Failed', ['message' => $e->getMessage(), 'url' => $apiUrl]);
                $statusCode = $e->getCode();
                \Log::info('API Request Status Code', ['status_code' => $statusCode]);

                if ($statusCode == 403) {
                    \Log::info('403 Forbidden - Using proxy', ['url' => $apiUrl]);
                    $proxytoken = 'e3980c24459441efbc4ca2d232d91e7663bfa3d6897';
                    $encodedUrl = urlencode($apiUrl);
                    $url = "https://api.scrape.do/?token=$proxytoken&url=$encodedUrl";
                    \Log::info('Using proxy URL', ['proxy_url' => $url]);
                    try {
                        $response = $client->get($url);
                        $api_route = 'proxy';
                    } catch (\Exception $e) {
                        $statusCode = $e->getCode();
                        \Log::info('Proxy request failed', ['status_code' => $statusCode, 'message' => $e->getMessage()]);
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
                // Check categories - if product has "peptide" category, always include it
                // Otherwise, skip if it has disallowed categories
                $categories = $item['categories'] ?? [];
                if (!empty($categories) && is_array($categories)) {
                    // First, check if product has a peptide category - if yes, always include it
                    $hasPeptideCategory = false;
                    foreach ($categories as $cat) {
                        $catName = strtolower($cat['name'] ?? '');
                        $catSlug = strtolower($cat['slug'] ?? '');
                        if (strpos($catName, 'peptide') !== false || strpos($catSlug, 'peptide') !== false) {
                            $hasPeptideCategory = true;
                            break;
                        }
                    }
                    
                    // If it has a peptide category, include it (don't check disallowed)
                    if (!$hasPeptideCategory) {
                        // If no peptide category, check for disallowed categories
                        $hasDisallowedCategory = false;
                        $disallowCategorieNames = [
                            'ebook',
                            'e-book',
                            'book',
                            'guide',
                            'manual',
                            'document',
                            'powder', // for pureawz
                            'capsules', // for pureawz
                            'research-caps', //for pureawz
                            'research-powders',
                            'stack-tablets',
                            'discounted',
                            'merchandise',
                            'supplementary',
                            'injectables',
                            'additions',
                        ];

                        foreach ($categories as $cat) {
                            $catName = strtolower($cat['name'] ?? '');
                            $catSlug = strtolower($cat['slug'] ?? '');
                            
                            // Check if category name or slug contains any disallowed name
                            foreach ($disallowCategorieNames as $disallowCategoryName) {
                                if (strpos($catName, $disallowCategoryName) !== false || strpos($catSlug, $disallowCategoryName) !== false) {
                                    $hasDisallowedCategory = true;
                                    break 2; // Break out of both loops
                                }
                            }
                        }
                        // Skip this product if it has a disallowed category (and no peptide category)
                        if ($hasDisallowedCategory) {
                            continue;
                        }
                    }
                }
                
                // Extract category name from product name
                // Example: "P21 Peptide 10MG (P021)" -> "P21 Peptide"
                $categoryName = $this->extractCategoryFromProductName($item['name'] ?? '');
                
                // Extract size from name or attributes
                $sizeMg = $this->extractSizeFromProduct($item);
                
                // Price conversion (cents to dollars)
                $price = isset($item['prices']['regular_price']) ? ((float)$item['prices']['regular_price'] / 100) : null;
                $discount_price = (isset($item['on_sale']) && $item['on_sale'] && isset($item['prices']['sale_price']))
                    ? ((float)$item['prices']['sale_price'] / 100)
                    : null;
                
                // Get first image URL
                $imageUrl = null;
                if (!empty($item['images']) && is_array($item['images'])) {
                    $imageUrl = $item['images'][0]['src'] ?? null;
                }
                
                // Get description (prefer short_description, fallback to description)
                $description = !empty($item['short_description']) ? $item['short_description'] : ($item['description'] ?? null);
                
                $products[] = [
                    'name' => $item['name'] ?? null,
                    'category_name' => $categoryName,
                    'description' => $description,
                    'price' => $price,
                    'discount_price' => $discount_price,
                    'second_price' => null,
                    'size_mg' => $sizeMg,
                    'image_url' => $imageUrl,
                    'product_url' => $item['permalink'] ?? null,
                    'is_in_stock' => $item['is_in_stock'] ?? false,
                    'is_on_backorder' => $item['is_on_backorder'] ?? false,
                    'stock_availability' => $item['stock_availability'] ?? null,
                ];
            }
            $page++;
        } while (count($data) === $perPage && $page <= 10); // safety limit
        \Log::info('Products fetched', ['count' => count($products)]);
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

        \Log::info('Running Python scraper', ['command' => $command, 'url' => $shopUrl]);

        $output = shell_exec($command);
        // \Log::info($output);
        $data = json_decode($output, true);
        if (isset($data['error'])) {
            return [];
        }
        return $data['products'];
    }

    /**
     * Extract category name from product name
     * Examples:
     * "Blend: BPC-157 TB-500 Peptide (20MG)" -> "bpc-157 tb-500"
     * "Blend KPV GHK-Cu BPC-157 TB-500" -> "kpv ghk-cu bpc-157 tb-500"
     * "P21 Peptide 10MG (P021)" -> "p21"
     * "BPC-157 Peptide (10MG) (Copy)" -> "bpc-157"
     * "Pack: Epitalon + Thymalin" -> "epitalon + thymalin"
     * "FOXO4-DRI Peptide (10MG)" -> "foxo4-dri"
     * "FOXO4-DRI Peptide 10MG" -> "foxo4-dri"
     * 
     * @param string $productName
     * @return string
     */
    private function extractCategoryFromProductName($productName)
    {
        if (empty($productName)) {
            return 'uncategorized';
        }
        
        $name = $productName;
        
        // Remove prefixes like "Blend:", "Blend ", "Pack:", "Pack ", "Powder:", "Powder " (with or without colon)
        $name = preg_replace('/^(Blend|Pack|Powder)[:\s]+/i', '', $name);
        
        // Remove size patterns (e.g., "10MG", "20MG", "30MG", "400MG/ML", "250mcg", "10ml", "10 ml") - can be in parentheses or standalone
        $name = preg_replace('/\s*\(?\s*\d+(?:\.\d+)?\s*(?:MG|mcg|ML|ml)(?:\/ML)?\s*\)?\s*/i', ' ', $name);
        
        // Handle parenthetical content
        // Remove codes like "(P021)" and indicators like "(Copy)"
        $name = preg_replace('/\s*\(P\d+\)\s*/i', ' ', $name); // Remove codes like (P021)
        $name = preg_replace('/\s*\(Copy\)\s*/i', ' ', $name); // Remove (Copy)
        $name = preg_replace('/\s*\(\d+(?:\.\d+)?\s*(?:MG|mcg|ML|ml)\)\s*/i', ' ', $name); // Remove size in parentheses like (10MG) or (10ml)
        $name = preg_replace('/\s*\(\d+\s*ct\)\s*/i', ' ', $name); // Remove count patterns like (100 ct)
        
        // Remove standalone count patterns like "100 ct" (not in parentheses)
        $name = preg_replace('/\s*\d+\s*ct\s*/i', ' ', $name);
        
        // Extract descriptive content from parentheses (e.g., "(No DAC)" -> "No DAC")
        $name = preg_replace_callback('/\s*\(([^)]+)\)\s*/', function($matches) {
            $content = trim($matches[1]);
            // Only keep if it looks like descriptive text (not just numbers or codes)
            if (preg_match('/[a-zA-Z]/', $content) && !preg_match('/^\d+(?:MG|ML|ml)$/i', $content)) {
                return ' ' . $content . ' ';
            }
            return ' ';
        }, $name);
        
        // Remove "Peptide" word (case insensitive)
        $name = preg_replace('/\s+Peptide\s*/i', ' ', $name);
        $name = preg_replace('/\s+Peptide\s*$/i', '', $name);
        
        // Remove "Capsules" and similar words (case insensitive)
        $name = preg_replace('/\s+Capsules?\s*/i', ' ', $name);
        $name = preg_replace('/\s+Capsules?\s*$/i', '', $name);
        
        // Remove "Water" word (case insensitive) - e.g., "bacteriostatic water 10 ml" -> "bacteriostatic"
        $name = preg_replace('/\s+Water\s*/i', ' ', $name);
        $name = preg_replace('/\s+Water\s*$/i', '', $name);
        
        // Remove "Blend" word (case insensitive) - e.g., "sermorelin ghrp-6 blend" -> "sermorelin ghrp-6"
        // Note: We already removed "Blend:" prefix, but need to remove standalone "blend" anywhere
        $name = preg_replace('/\s+Blend\s*/i', ' ', $name);
        $name = preg_replace('/\s+Blend\s*$/i', '', $name);

        $name = preg_replace('/\s+Tablets\s*/i', ' ', $name);
        $name = preg_replace('/\s+Tablets\s*$/i', '', $name);

        // Replace "+" with space (e.g., "BAM-15 + SLU-PP-332" -> "BAM-15 SLU-PP-332")
        $name = preg_replace('/\s*\+\s*/', ' ', $name);
        
        // Clean up whitespace
        $name = trim($name);
        
        // If empty after cleaning, return uncategorized
        if (empty($name)) {
            return 'uncategorized';
        }
        
        // Convert to lowercase as requested
        $name = strtolower($name);
        
        return $name;
    }

    /**
     * Extract size in MG from product data
     * 
     * @param array $item Product data from API
     * @return float|null
     */
    private function extractSizeFromProduct($item)
    {
        // First, try to get from attributes (Weight attribute)
        if (isset($item['attributes']) && is_array($item['attributes'])) {
            foreach ($item['attributes'] as $attribute) {
                if (isset($attribute['name']) && 
                    (stripos($attribute['name'], 'weight') !== false || 
                     stripos($attribute['name'], 'size') !== false)) {
                    if (isset($attribute['terms']) && is_array($attribute['terms']) && !empty($attribute['terms'])) {
                        $term = $attribute['terms'][0]['name'] ?? null;
                        if ($term) {
                            // Extract number from "20MG" or "20 MG"
                            if (preg_match('/(\d+(?:\.\d+)?)\s*MG/i', $term, $matches)) {
                                return (float) $matches[1];
                            }
                        }
                    }
                }
            }
        }
        
        // Second, try to extract from product name
        // Example: "P21 Peptide 10MG (P021)" -> 10
        if (isset($item['name'])) {
            if (preg_match('/(\d+(?:\.\d+)?)\s*MG/i', $item['name'], $matches)) {
                return (float) $matches[1];
            }
        }
        
        // Third, check short_description for weight info
        if (isset($item['short_description'])) {
            if (preg_match('/WEIGHT[^>]*>([^<]+)/i', $item['short_description'], $matches)) {
                $weightText = trim(strip_tags($matches[1]));
                if (preg_match('/(\d+(?:\.\d+)?)\s*MG/i', $weightText, $sizeMatches)) {
                    return (float) $sizeMatches[1];
                }
            }
        }
        
        return null;
    }
} 