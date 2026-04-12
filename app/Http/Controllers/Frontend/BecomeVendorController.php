<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Brand;
use App\Models\User;
use App\Models\VendorSetting;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\VendorWelcomeEmail;
use App\Mail\NewVendorNotification;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class BecomeVendorController extends Controller
{
    /**
     * Show the become a vendor page
     */
    public function index(Request $request)
    {
        $step = $request->get('step', 1);
        $step = max(1, min(4, (int) $step)); // Ensure step is between 1 and 4

        $locations = Location::orderBy('name')->get(['id', 'name']);

        // Generate SEO data
        $seoData = new SEOData(
            title: 'Become a Vendor - Join PeptideSync Marketplace | PeptideSync',
            description: 'Join PeptideSync as a vendor and reach thousands of researchers. List your products, manage inventory, and grow your peptide business.',
            url: url('/become-a-vendor'),
        );
        session(['page_seo_data' => $seoData]);

        return Inertia::render('Frontend/BecomeVendor', [
            'step' => $step,
            'locations' => $locations,
        ]);
    }

    /**
     * Store vendor registration data
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Step 1: Company Information
            'companyName' => 'required|string|max:255',
            'website' => 'required|url|max:255',
            'yearEstablished' => 'nullable|integer|min:1800|max:' . date('Y'),
            'country' => 'required|exists:locations,id',
            
            // Step 2: Contact Details
            'fullName' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:8|confirmed',
            
            // Step 3: Business Info
            'salesVolume' => 'nullable|string|max:50',
            'productCount' => 'nullable|string|max:50',
            'companyDescription' => 'nullable|string|max:2000',
            'paymentMethods' => 'nullable|array',
            'paymentMethods.*' => 'nullable|string|in:Credit Card,PayPal,Cryptocurrency,Bank Transfer',
            'shippingInformation' => 'nullable|string|max:2000',
            'returnPolicy' => 'nullable|string|max:2000',
            'businessHours' => 'nullable|string|max:255',
            'uniqueSellingPoints' => 'nullable|string|max:2000',
            'logoFile' => 'nullable|mimes:png|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Check if brand name already exists
            if (Brand::where('name', $validated['companyName'])->exists()) {
                return back()->withErrors(['companyName' => 'This company name is already taken.'])->withInput();
            }

            // Create User account
            $user = User::create([
                'name' => $validated['fullName'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'vendor',
            ]);

            // Create Brand
            $brand = Brand::create([
                'name' => $validated['companyName'],
                'user_id' => $user->id,
                'is_active' => false, // Inactive until admin approves
            ]);

            // Build description with unique selling points appended
            $description = $validated['companyDescription'] ?? '';
            if (!empty($validated['uniqueSellingPoints'])) {
                if (!empty($description)) {
                    $description .= "\n\nUnique Selling Points:\n" . $validated['uniqueSellingPoints'];
                } else {
                    $description = "Unique Selling Points:\n" . $validated['uniqueSellingPoints'];
                }
            }

            // Create VendorSetting
            $settings = new VendorSetting([
                'brand_id' => $brand->id,
                'location_id' => $validated['country'],
                'description' => $description,
                'contact_email' => $validated['email'],
                'phone_number' => $validated['phone'] ?? null,
                'shop_url' => $validated['website'],
                'website' => $validated['website'],
                'founded_year' => !empty($validated['yearEstablished']) ? (int)$validated['yearEstablished'] : null,
                'shipping_info' => $validated['shippingInformation'] ?? null,
                'return_policy' => $validated['returnPolicy'] ?? null,
                'business_hours' => $validated['businessHours'] ?? null,
                'payment_methods' => $validated['paymentMethods'] ?? null,
                'status' => 0, // Inactive until approved
                'approval_status' => 'pending', // Pending approval
            ]);

            // Handle logo upload
            if ($request->hasFile('logoFile')) {
                $logoFile = $request->file('logoFile');
                $extension = strtolower($logoFile->getClientOriginalExtension());
                $mimeType = $logoFile->getMimeType();
                
                // Check if it's PNG
                if ($extension === 'png' || $mimeType === 'image/png') {
                    // Convert PNG to WebP
                    try {
                        $logoFilename = ImageHelper::convertToWebP($logoFile, 'vendor_logos');
                        $settings->logo = 'vendor_logos/' . $logoFilename;
                    } catch (\Exception $e) {
                        // If WebP conversion fails, save as PNG
                        $logoFilename = Str::random(40) . '.png';
                        $logoFile->storeAs('vendor_logos', $logoFilename, 'public');
                        $settings->logo = 'vendor_logos/' . $logoFilename;
                    }
                }
            }

            $settings->save();

            DB::commit();

            // Send email verification notification
            $user->sendEmailVerificationNotification();

            // Send welcome email with credentials to vendor
            try {
                Mail::to($validated['email'])->send(new VendorWelcomeEmail(
                    companyName: $validated['companyName'],
                    email: $validated['email'],
                    password: $validated['password'],
                    loginUrl: url('/login'),
                ));
            } catch (\Throwable $e) {
                \Log::warning('Failed to send vendor welcome email', ['email' => $validated['email'], 'error' => $e->getMessage()]);
            }

            // Notify admin of new vendor signup
            try {
                $locationName = isset($validated['country']) ? Location::find($validated['country'])?->name : null;
                Mail::to('info@peptidemap.com')->send(new NewVendorNotification(
                    brand: $brand,
                    contactEmail: $validated['email'],
                    website: $validated['website'] ?? '',
                    phone: $validated['phoneNumber'] ?? null,
                    country: $locationName,
                    description: $validated['companyDescription'] ?? null,
                ));
            } catch (\Throwable $e) {
                \Log::warning('Failed to send admin vendor notification', ['brand' => $brand->id, 'error' => $e->getMessage()]);
            }

            return back()->with('success', 'Registration completed successfully. Your account is currently under review and should be activated shortly.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'An error occurred during registration. Please try again.'])->withInput();
        }
    }
}
