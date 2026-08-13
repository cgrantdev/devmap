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
use Illuminate\Support\Facades\Cache;
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
            title: 'Become a Vendor — Peptidemap',
            description: 'List your peptides on Peptidemap — free during beta. Auto-sync from WooCommerce, Medusa, BigCommerce. Vendor analytics + PMAP coupon integration.',
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
            'companyName' => 'required|string|min:2|max:255',
            'website' => ['required', 'url:http,https', 'max:255'],
            'yearEstablished' => 'nullable|integer|min:1800|max:' . date('Y'),
            'country' => 'required|exists:locations,id',

            // Step 2: Contact Details
            'fullName' => 'required|string|min:2|max:255',
            'email' => 'required|email:rfc,dns|max:255|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-z]/',     // lowercase
                'regex:/[A-Z]/',     // uppercase
                'regex:/[0-9]/',     // number
            ],

            // Step 3 + 4: Business Info + REST API credentials.
            // API keys are required UNLESS the vendor explicitly opts out via
            // refuseApiAccess=true (in which case manual mode is used).
            'connectionMethod' => 'nullable|string|in:woocommerce,api_key,auto_scrape,manual',
            'refuseApiAccess' => 'nullable|boolean',
            'apiConsumerKey' => ['nullable', 'required_without:refuseApiAccess', 'string', 'min:10', 'max:255', 'starts_with:ck_'],
            'apiConsumerSecret' => ['nullable', 'required_without:refuseApiAccess', 'string', 'min:10', 'max:255', 'starts_with:cs_'],
            'productCount' => 'nullable|string|max:50',
            'companyDescription' => 'nullable|string|max:5000',
            'paymentMethods' => 'nullable|array',
            'paymentMethods.*' => 'nullable|string|in:Credit Card,PayPal,Cryptocurrency,Bank Transfer',
            'shippingInformation' => 'nullable|string|max:5000',
            'returnPolicy' => 'nullable|string|max:5000',
            'businessHours' => 'nullable|string|max:500',
            'uniqueSellingPoints' => 'nullable|string|max:5000',
            // Logo: accept PNG/JPG/JPEG/WebP/SVG up to 8 MB. Real vendor logos
            // are often 2-5 MB (transparent backgrounds, full-resolution) and
            // the older 2 MB PNG-only rule was bouncing legitimate signups.
            'logoFile' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:8192',
        ], [
            'password.regex' => 'Password must include at least one uppercase, one lowercase, and one number.',
            'apiConsumerKey.starts_with' => 'Consumer Key must start with "ck_".',
            'apiConsumerSecret.starts_with' => 'Consumer Secret must start with "cs_".',
            'website.url' => 'Please enter a valid URL starting with http:// or https://.',
            'email.email' => 'Please enter a valid email address.',
            'logoFile.mimes' => 'Logo must be a PNG, JPG, WebP, or SVG file.',
            'logoFile.max' => 'Logo file is too large. Please use a file under 8 MB.',
            'companyDescription.max' => 'Company description is too long (5,000 character limit).',
            'shippingInformation.max' => 'Shipping information is too long (5,000 character limit).',
            'returnPolicy.max' => 'Return policy is too long (5,000 character limit).',
            'uniqueSellingPoints.max' => 'Unique selling points is too long (5,000 character limit).',
        ]);

        try {
            DB::beginTransaction();

            // Check if brand name already exists
            if (Brand::where('name', $validated['companyName'])->exists()) {
                return back()->withErrors(['companyName' => 'This company name is already taken.'])->withInput();
            }

            // Create User account.
            // Vendor email verification is handled by the admin during the
            // approval flow — we don't make applicants verify themselves.
            // email_verified_at is set when admin clicks Approve.
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
            // Note: applicant explicitly opted out of providing API access.
            // Surfaces in the admin /applicants queue so staff know to follow up.
            if (!empty($validated['refuseApiAccess'])) {
                $description .= ($description ? "\n\n" : '') . "[Refused API access — manual updates only]";
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
                'api_platform' => match ($validated['connectionMethod'] ?? null) {
                    'woocommerce', 'api_key' => 'woocommerce',
                    'auto_scrape' => 'page_scrape',
                    default => null,
                },
            ]);

            // Handle logo upload — accept PNG/JPG/WebP/SVG. PNG/JPG/WebP get
            // converted to WebP for storage savings; SVG is kept as-is since
            // it's already vector. Previously the controller silently dropped
            // anything that wasn't PNG, which is why some signups looked
            // logo-less after submitting.
            if ($request->hasFile('logoFile')) {
                $logoFile = $request->file('logoFile');
                $extension = strtolower($logoFile->getClientOriginalExtension());
                $mimeType = $logoFile->getMimeType();

                $isRaster = in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)
                    || in_array($mimeType, ['image/png', 'image/jpeg', 'image/webp'], true);
                $isSvg = $extension === 'svg' || $mimeType === 'image/svg+xml';

                if ($isRaster) {
                    try {
                        $logoFilename = ImageHelper::convertToWebP($logoFile, 'vendor_logos');
                        $settings->logo = 'vendor_logos/' . $logoFilename;
                    } catch (\Exception $e) {
                        // WebP conversion failed (lib missing, malformed file).
                        // Fall back to storing the original under a random name
                        // so the vendor's logo still lands somewhere.
                        $safeExt = in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true) ? $extension : 'png';
                        $logoFilename = Str::random(40) . '.' . $safeExt;
                        $logoFile->storeAs('vendor_logos', $logoFilename, 'public');
                        $settings->logo = 'vendor_logos/' . $logoFilename;
                    }
                } elseif ($isSvg) {
                    $logoFilename = Str::random(40) . '.svg';
                    $logoFile->storeAs('vendor_logos', $logoFilename, 'public');
                    $settings->logo = 'vendor_logos/' . $logoFilename;
                }
            }

            $settings->save();

            // Create ScrapingConfig if API keys provided
            if (!empty($validated['apiConsumerKey']) && !empty($validated['apiConsumerSecret'])) {
                \App\Models\ScrapingConfig::create([
                    'vendor_id' => $brand->id,
                    'vendor_name' => $brand->name,
                    'type' => 'woo_api',
                    'store_url' => $validated['website'],
                    'products_url' => $validated['website'],
                    'auth_credentials' => [
                        'consumer_key' => $validated['apiConsumerKey'],
                        'consumer_secret' => $validated['apiConsumerSecret'],
                    ],
                    'enabled' => true,
                    'frequency' => 'daily',
                    'auto_promote' => true,
                ]);
            }

            DB::commit();

            // Bust the site-wide location dropdown cache so a new vendor's
            // country appears in the header CountrySelector immediately
            // instead of waiting up to 10 min. Keyed to match
            // HandleInertiaRequests::share().
            Cache::forget('site_locations_v1');

            // No email-verification flow for vendors — the admin verifies
            // them manually during the approval step instead.

            // Send welcome / "application received" email
            try {
                Mail::to($validated['email'])->send(new VendorWelcomeEmail(
                    companyName: $validated['companyName'],
                    email: $validated['email'],
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

            // Stash the brand details in session so the confirmation page
            // can show a personalized "thanks" without exposing IDs in URL.
            session([
                'registration_complete' => [
                    'company' => $validated['companyName'],
                    'email' => $validated['email'],
                    'connection_token' => $brand->connection_token,
                    'refused_api' => !empty($validated['refuseApiAccess']),
                    'submitted_at' => now()->toIso8601String(),
                ],
            ]);

            return redirect('/registration-complete');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error('Vendor signup DB exception', [
                'sql_state' => $e->errorInfo[0] ?? null,
                'driver_code' => $e->errorInfo[1] ?? null,
                'message' => $e->getMessage(),
                'email' => $request->input('email'),
                'company' => $request->input('companyName'),
            ]);

            // Map common DB errors to friendly user-facing messages
            $message = 'There was a database error during registration. Please try again or contact support.';
            $errorCode = $e->errorInfo[1] ?? null;

            if ($errorCode === 1062) { // Duplicate entry
                if (str_contains($e->getMessage(), 'users_email_unique') || str_contains($e->getMessage(), 'email')) {
                    return back()->withErrors(['email' => 'This email is already registered.'])->withInput();
                }
                if (str_contains($e->getMessage(), 'brands_slug')) {
                    return back()->withErrors(['companyName' => 'This company name is already taken.'])->withInput();
                }
                $message = 'A duplicate record was detected. Please check your details and try again.';
            }

            return back()->withErrors(['error' => $message])->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Vendor signup unexpected exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->input('email'),
                'company' => $request->input('companyName'),
            ]);

            $userMessage = 'An unexpected error occurred during registration. Our team has been notified — please try again or contact support@peptidemap.com.';

            // Surface the actual message in non-production environments to help debugging
            if (app()->environment(['local', 'staging'])) {
                $userMessage = '[' . app()->environment() . '] ' . $e->getMessage();
            }

            return back()->withErrors(['error' => $userMessage])->withInput();
        }
    }

    /**
     * Confirmation page shown after a successful vendor signup.
     * Reads the company name from session (set by store() above).
     * Direct visits without a session payload bounce back to /become-a-vendor.
     */
    public function complete(Request $request)
    {
        $payload = session('registration_complete');

        if (!$payload || empty($payload['company'])) {
            return redirect('/become-a-vendor');
        }

        // Clear the session payload so a refresh doesn't keep it around
        session()->forget('registration_complete');

        return Inertia::render('Frontend/RegistrationComplete', [
            'company' => $payload['company'],
            'email' => $payload['email'] ?? null,
        ]);
    }
}
