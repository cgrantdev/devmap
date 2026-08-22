<?php

use Illuminate\Support\Facades\Route;

use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\Auth\VendorAuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use App\Http\Controllers\Vendor\VendorSettingsController;
use App\Http\Controllers\Vendor\PublicVendorController;
use App\Http\Controllers\Admin\VendorsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\BlogManagementController;
use App\Http\Controllers\Admin\ResearchManagementController;
// EducationalGuideManagementController + EducationPostsController removed
use App\Http\Controllers\Admin\EncyclopediaEntriesManagementController;
use App\Http\Controllers\Admin\CategoriesController;
use App\Http\Controllers\Admin\LocationsController;
use App\Http\Controllers\Admin\ProductsController as AdminProductsController;
use App\Http\Controllers\Admin\PagesController;
use App\Http\Controllers\Admin\ScrapingConfigController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Vendor\ImportController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Frontend\ProductsController;
use App\Http\Controllers\Frontend\BrandsController;
// Note: BrandsController also serves /vendors (see route below)
use App\Http\Controllers\Frontend\BlogsController;
// EducationController removed
use App\Http\Controllers\Frontend\EncyclopediaController;
use App\Http\Controllers\Frontend\CompareController;
use App\Http\Controllers\Frontend\KnowledgeCenterController;
use App\Http\Controllers\Frontend\VendorReviewsController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\BecomeVendorController;
use App\Http\Controllers\Frontend\JoinController;
use App\Http\Controllers\Frontend\DemoPreviewController;
use App\Http\Controllers\Frontend\DealsController;
use App\Http\Controllers\Frontend\PagesController as FrontendPagesController;

// join.peptidemap.com is a single-purpose subdomain — its root path
// serves the vendor invitation page, not the marketplace homepage.
// Must be registered BEFORE the bare "/" route or Laravel's first-match
// resolution will hand the request to HomeController.
Route::domain('join.peptidemap.com')->group(function () {
    Route::get('/', [JoinController::class, 'show'])->name('join.root');
});

// Modern 2026 homepage is now the default
// SEO — sitemap + host-aware robots.txt. Both are cached at the controller layer.
Route::get('/sitemap.xml', [\App\Http\Controllers\Frontend\SitemapController::class, 'index']);
Route::get('/robots.txt', [\App\Http\Controllers\Frontend\RobotsController::class, 'show']);

// Per-URL OG images (server-rendered via headless Chromium, disk-cached).
Route::get('/og/product/{id}.png', [\App\Http\Controllers\Frontend\ProductOgImageController::class, 'show'])
    ->whereNumber('id')->name('og.product');
Route::get('/og/brand/{slug}.png', [\App\Http\Controllers\Frontend\BrandOgImageController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')->name('og.brand');
Route::get('/og/compound/{slug}.png', [\App\Http\Controllers\Frontend\CompoundOgImageController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')->name('og.compound');

Route::get('/', [HomeController::class, 'v2'])->name('home');
// Legacy homepage (kept for reference, remove when fully migrated)
Route::get('/home-old', [HomeController::class, 'index'])->name('home.old');
// Preview route still works as an alias
Route::get('/home-v2', [HomeController::class, 'v2'])->name('home.v2');

// Affiliate outbound redirect (click tracking)
Route::get('/go/{product}', [\App\Http\Controllers\OutboundClickController::class, 'redirect'])
    ->middleware('throttle:60,1')
    ->name('product.go');

// WooCommerce auth callback (no CSRF — called by WooCommerce server)
Route::post('/api/woo-auth-callback', [\App\Http\Controllers\Admin\WooCommerceAuthController::class, 'handleCallback'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Newsletter subscribe (no CSRF — used by static coming soon page)
Route::post('/api/subscribe', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate(['email' => 'required|email|max:255']);
    \App\Models\NewsletterSubscriber::firstOrCreate(
        ['email' => strtolower(trim($validated['email']))],
        ['source' => $request->input('source', 'coming_soon')]
    );
    return response()->json(['ok' => true, 'message' => 'You\'re on the list!']);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// WordPress plugin connect endpoint (no CSRF — called from external WP installs)
Route::post('/api/vendor-plugin/connect', [\App\Http\Controllers\Api\VendorPluginController::class, 'connect'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->name('api.vendor-plugin.connect');

// Search
Route::get('/search', [\App\Http\Controllers\Frontend\SearchController::class, 'index'])->name('search');

// Frontend pages
Route::get('/products', [ProductsController::class, 'index'])->name('products');
Route::get('/product/{vendorSlug}/{productSlug}/{id}', [ProductsController::class, 'showProduct'])
    ->whereNumber('id')
    ->name('product.detail');
Route::get('/product/{slug}/{id}', function ($slug, $id) {
    $product = \App\Models\Product::with('brand')->find($id);
    if (!$product || !$product->brand) {
        abort(404);
    }
    return redirect()->route('product.detail', [
        'vendorSlug' => $product->brand->slug,
        'productSlug' => $product->slug,
        'id' => $product->id,
    ], 301);
})->whereNumber('id')->name('product.detail.legacy');
Route::get('/product/{slug}', [ProductsController::class, 'show'])->name('product.show');
Route::get('/brand/{slug}', [ProductsController::class, 'byBrand'])->name('brand.products');
Route::get('/brand/{slug}/products', function ($slug) {
    return redirect()->route('brand.products', ['slug' => $slug], 301);
})->name('brand.products.legacy');
Route::get('/brands', [BrandsController::class, 'index'])->name('brands');
Route::get('/blogs', [BlogsController::class, 'index'])->name('blogs');
Route::get('/blog/{slug}', [BlogsController::class, 'show'])->name('blog.show');
// Education posts removed — content now lives in Encyclopedia + Blog
Route::get('/encyclopedia', [EncyclopediaController::class, 'index'])->name('encyclopedia');
Route::get('/encyclopedia/{slug}', [EncyclopediaController::class, 'showArticle'])->name('encyclopedia.show');
// Legacy /encyclopedia/article/{slug} redirects to clean URL
Route::get('/encyclopedia/article/{slug}', fn ($slug) => redirect("/encyclopedia/{$slug}", 301));
// Dedicated /bacteriostatic-water landing page — must come BEFORE the
// /{slug} catch-all at the bottom of this file.
Route::get('/bacteriostatic-water', [\App\Http\Controllers\Frontend\BacteriostaticWaterController::class, 'show'])
    ->name('bacteriostatic-water');

Route::get('/compare', [CompareController::class, 'index'])->name('compare');
// Per-compound compare pages — /compare/bpc-157, /compare/semaglutide, etc.
// Placed AFTER /compare (specific) but constrained to [a-z0-9-]+ so it
// doesn't shadow future /compare/{action}-style additions.
Route::get('/compare/{slug}', [CompareController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('compare.compound');
Route::get('/calculator', function () {
    $seoPage = \App\Models\SeoPage::where('key', 'calculator')->first();
    $defaultTitle = 'Peptide Reconstitution Calculator — Peptidemap';
    $defaultDescription = 'Calculate concentration, volume, and doses per vial for 17+ popular peptides. Free tool with vial-size, BAC volume, and target-dose presets — no signup.';
    $seo = [
        'key' => 'calculator',
        'title' => $seoPage?->title ?: $defaultTitle,
        'description' => $seoPage?->description ?: $defaultDescription,
        'og_title' => $seoPage?->og_title ?: ($seoPage?->title ?: $defaultTitle),
        'og_description' => $seoPage?->og_description ?: ($seoPage?->description ?: $defaultDescription),
        'og_image' => $seoPage?->og_image ?: null,
        'url' => url('/calculator'),
    ];
    session(['page_seo_data' => $seo]);

    return Inertia::render('Frontend/Calculator', [
        'seo' => $seo,
    ]);
})->name('calculator');
Route::get('/news', [KnowledgeCenterController::class, 'index'])->name('news');
Route::get('/research/{id}', [KnowledgeCenterController::class, 'showResearch'])->name('research.show');
Route::get('/deals', [DealsController::class, 'index'])->name('deals');
Route::get('/become-a-vendor', [BecomeVendorController::class, 'index'])->name('become-a-vendor');
Route::post('/become-a-vendor', [BecomeVendorController::class, 'store'])->name('become-a-vendor.store');
Route::get('/registration-complete', [BecomeVendorController::class, 'complete'])->name('registration.complete');

// /join works on any host — used for testing on dev.peptidemap.com.
// The join.peptidemap.com root route is registered earlier in this file.
Route::get('/join', [JoinController::class, 'show'])->name('join');

// Demo preview — public entry-point that auto-logs visitors into the
// seeded Helix Research Co. demo vendor account. Restricted to the
// demo.peptidemap.com host inside the controller.
Route::post('/demo/preview-vendor', [DemoPreviewController::class, 'start'])->name('demo.preview-vendor');
Route::post('/demo/exit', [DemoPreviewController::class, 'exit'])->name('demo.exit');

// Vendor Reviews
Route::post('/brands/{brandId}/reviews', [VendorReviewsController::class, 'store'])
    ->middleware(['auth', 'email.verified', 'throttle:5,60'])
    ->name('vendor.reviews.store');
Route::get('/brands/{brandId}/reviews', [VendorReviewsController::class, 'index'])->name('vendor.reviews.index');

// Customer account — manage own reviews
Route::middleware(['auth', 'email.verified'])->prefix('account')->group(function () {
    Route::get('/reviews', [\App\Http\Controllers\Frontend\AccountReviewsController::class, 'index'])->name('account.reviews');
    Route::put('/reviews/{id}', [\App\Http\Controllers\Frontend\AccountReviewsController::class, 'update'])->name('account.reviews.update');
    Route::delete('/reviews/{id}', [\App\Http\Controllers\Frontend\AccountReviewsController::class, 'destroy'])->name('account.reviews.destroy');
    Route::get('/wishlist', [\App\Http\Controllers\Frontend\WishlistController::class, 'index'])->name('account.wishlist');
    Route::delete('/wishlist/{id}', [\App\Http\Controllers\Frontend\WishlistController::class, 'destroy'])->name('account.wishlist.destroy');
});

// Wishlist toggle — auth-required + rate-limited to prevent spam. Registered
// well before the catch-all /{slug} route below so it isn't shadowed.
Route::post('/wishlist/toggle', [\App\Http\Controllers\Frontend\WishlistController::class, 'toggle'])
    ->middleware(['auth', 'throttle:60,1'])
    ->name('wishlist.toggle');

// Guest routes
Route::middleware('guest')->group(function () {
    // Vendor routes
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    // All login pages redirect to unified /login
    Route::get('/vendor/login', fn () => redirect('/login'))->name('vendor.login');
    Route::get('/admin/login', fn () => redirect('/login'))->name('admin.login');
});

// Email verification routes (require authentication but not email verification)
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'show'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->name('verification.send');
});

// Email verification link (no auth required as it's clicked from email)
// This route handles both signed URLs and route parameters
Route::get('/email/verify/{id?}/{hash?}', [EmailVerificationController::class, 'verify'])->name('verification.verify');

// Vendor routes
Route::middleware(['auth', 'role:vendor', 'email.verified'])->prefix('vendor')->group(function () {
    Route::get('/dashboard', [VendorDashboardController::class, 'index'])->name('vendor.dashboard');
    Route::get('/storefront-analytics', [VendorDashboardController::class, 'storefrontAnalytics'])->name('vendor.storefront-analytics');
    Route::get('/advertisement-analytics', [VendorDashboardController::class, 'advertisementAnalytics'])->name('vendor.advertisement-analytics');
    Route::get('/reviews', [VendorDashboardController::class, 'reviews'])->name('vendor.reviews');
    Route::post('/reviews/{id}/reply', [VendorDashboardController::class, 'reply'])->name('vendor.reviews.reply');
    Route::post('/reviews/{id}/flag', [VendorDashboardController::class, 'flag'])->name('vendor.reviews.flag');
    Route::post('/reviews/{id}/unflag', [VendorDashboardController::class, 'unflag'])->name('vendor.reviews.unflag');
    Route::get('/import', [ImportController::class, 'index'])->name('vendor.import');
    Route::post('/import/file', [ImportController::class, 'importFromFile'])->name('vendor.import.file');
    Route::post('/import/url', [ImportController::class, 'importFromUrl'])->name('vendor.import.url');
    Route::get('/products', [VendorDashboardController::class, 'products'])->name('vendor.products');
    Route::get('/products/{product}/edit', [VendorDashboardController::class, 'editProduct'])->name('vendor.products.edit');
    Route::put('/products/{product}', [VendorDashboardController::class, 'updateProduct'])->name('vendor.products.update');
    Route::patch('/products/{product}/hidden', [VendorDashboardController::class, 'setProductHidden'])->name('vendor.products.hidden');
    Route::post('/products/bulk-hidden', [VendorDashboardController::class, 'bulkSetProductHidden'])->name('vendor.products.bulk-hidden');
    Route::delete('/products/{product}', [ImportController::class, 'deleteProduct'])->name('vendor.products.delete');
    Route::get('/profile', [VendorDashboardController::class, 'profile'])->name('vendor.profile');
    Route::post('/profile', [VendorDashboardController::class, 'updateProfile'])->name('vendor.profile.update');
    Route::get('/settings', [VendorSettingsController::class, 'show'])->name('vendor.settings');
    Route::post('/settings', [VendorSettingsController::class, 'update'])->name('vendor.settings.update');

    // WooCommerce + other vendor integrations
    Route::get('/integrations', [\App\Http\Controllers\Vendor\IntegrationsController::class, 'show'])->name('vendor.integrations');
    Route::post('/integrations/woo', [\App\Http\Controllers\Vendor\IntegrationsController::class, 'store'])->name('vendor.integrations.woo.store');
    Route::post('/integrations/woo/sync', [\App\Http\Controllers\Vendor\IntegrationsController::class, 'sync'])->name('vendor.integrations.woo.sync');
    Route::delete('/integrations/woo', [\App\Http\Controllers\Vendor\IntegrationsController::class, 'destroy'])->name('vendor.integrations.woo.destroy');
});

// Admin routes
Route::middleware(['auth', 'role:admin,admin_viewer', 'email.verified', 'block.viewer.writes'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // CEO-only dashboard. ceo.only middleware locks the endpoint down to
    // info@peptidemap.com; sidebar link is gated separately in Vue via
    // the `is_ceo` shared prop.
    Route::middleware('ceo.only')->prefix('ceo')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CeoDashboardController::class, 'index'])->name('admin.ceo');
        Route::post('/agent-runs', [\App\Http\Controllers\Admin\CeoDashboardController::class, 'storeAgentRun'])->name('admin.ceo.agent-run.store');
        Route::post('/recommendations', [\App\Http\Controllers\Admin\CeoDashboardController::class, 'storeRecommendation'])->name('admin.ceo.rec.store');
        Route::patch('/recommendations/{recommendation}', [\App\Http\Controllers\Admin\CeoDashboardController::class, 'updateRecommendation'])->name('admin.ceo.rec.update');
        Route::delete('/recommendations/{recommendation}', [\App\Http\Controllers\Admin\CeoDashboardController::class, 'destroyRecommendation'])->name('admin.ceo.rec.destroy');
        Route::post('/notepad', [\App\Http\Controllers\Admin\CeoDashboardController::class, 'saveNotepad'])->name('admin.ceo.notepad.save');
    });
    Route::get('/vendors', [VendorsController::class, 'index'])->name('admin.vendors');
    Route::get('/applicants', [VendorsController::class, 'applicants'])->name('admin.applicants');
    Route::get('/vendors/create', [VendorsController::class, 'create'])->name('admin.vendors.create');
    Route::post('/vendors', [VendorsController::class, 'store'])->name('admin.vendors.store');
    Route::get('/vendors/{id}/edit', [VendorsController::class, 'edit'])->name('admin.vendors.edit');
    Route::post('/vendors/{id}', [VendorsController::class, 'update'])->name('admin.vendors.update');
    Route::delete('/vendors/{id}', [VendorsController::class, 'destroy'])->name('admin.vendors.destroy');
    Route::post('/vendors/{id}/toggle-status', [VendorsController::class, 'toggleStatus'])->name('admin.vendors.toggle-status');
    Route::post('/vendors/{id}/toggle-demo', [VendorsController::class, 'toggleDemo'])->name('admin.vendors.toggle-demo');
    Route::post('/vendors/{id}/approve', [VendorsController::class, 'approve'])->name('admin.vendors.approve');
    Route::post('/vendors/{id}/reject', [VendorsController::class, 'reject'])->name('admin.vendors.reject');
    // Vendor Discovery
    Route::get('/discover', [\App\Http\Controllers\Admin\VendorDiscoveryController::class, 'index'])->name('admin.discover');
    Route::post('/discover/scan', [\App\Http\Controllers\Admin\VendorDiscoveryController::class, 'scan'])->name('admin.discover.scan');
    Route::post('/discover/import', [\App\Http\Controllers\Admin\VendorDiscoveryController::class, 'import'])->name('admin.discover.import');
    Route::post('/discover/activate', [\App\Http\Controllers\Admin\VendorDiscoveryController::class, 'activate'])->name('admin.discover.activate');

    // Admin impersonation — log in as a vendor user to view their dashboard
    Route::get('/impersonate/{userId}', function ($userId) {
        $admin = auth()->user();
        if (!$admin || $admin->role !== 'admin') abort(403);
        $target = \App\Models\User::findOrFail($userId);
        session(['impersonating_from' => $admin->id]);
        auth()->login($target);
        return redirect('/vendor/dashboard');
    })->name('admin.impersonate');

    // Stop impersonation route moved outside admin middleware (see below)

    Route::post('/vendors/{id}/scrape', [VendorsController::class, 'triggerScrape'])->name('admin.vendors.scrape');
    Route::post('/vendors/{id}/discover-products', [VendorsController::class, 'discoverProducts'])->name('admin.vendors.discover-products');
    Route::get('/vendors/{id}/woo-connect', [\App\Http\Controllers\Admin\WooCommerceAuthController::class, 'generateAuthUrl'])->name('admin.vendors.woo-connect');
    Route::get('/vendors/{id}/products', [VendorsController::class, 'products'])->name('admin.vendors.products');
    Route::post('/vendors/{id}/products/import', [VendorsController::class, 'importProductsFromFile'])->name('admin.vendors.products.import');
    Route::post('/vendors/{id}/products/import-url', [VendorsController::class, 'importProductsFromUrl'])->name('admin.vendors.products.import-url');
    Route::post('/vendors/{id}/import-shop-url', [VendorsController::class, 'importFromShopUrl'])->name('admin.vendors.import-shop-url');
    Route::delete('/vendors/{vendorId}/products/{productId}', [VendorsController::class, 'deleteProduct'])->name('admin.vendors.products.delete');
    Route::get('/products', [VendorsController::class, 'adminProducts'])->name('admin.products');
    Route::get('/products/create', [AdminProductsController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [AdminProductsController::class, 'store'])->name('admin.products.store');
    Route::get('/products/{id}/edit', [AdminProductsController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{id}', [AdminProductsController::class, 'update'])->name('admin.products.update');
    Route::patch('/products/{id}/hidden', [AdminProductsController::class, 'setHidden'])->name('admin.products.hidden');
    Route::patch('/products/{id}/auto-update', [AdminProductsController::class, 'setAutoUpdate'])->name('admin.products.auto-update');
    // Bulk endpoints MUST be declared before the wildcard {id} routes below,
    // otherwise Laravel will match /products/bulk-delete as DELETE /products/{id="bulk-delete"}.
    Route::patch('/products/bulk-update', [AdminProductsController::class, 'bulkUpdate'])->name('admin.products.bulk-update');
    Route::delete('/products/bulk-delete', [AdminProductsController::class, 'bulkDelete'])->name('admin.products.bulk-delete');
    Route::patch('/products/{id}/quick-update', [AdminProductsController::class, 'quickUpdate'])->name('admin.products.quick-update');
    Route::delete('/products/{id}', [AdminProductsController::class, 'destroy'])->name('admin.products.destroy');
    
    // Categories
    Route::get('/categories', [CategoriesController::class, 'index'])->name('admin.categories.index');
    Route::get('/categories/create', [CategoriesController::class, 'create'])->name('admin.categories.create');
    Route::post('/categories', [CategoriesController::class, 'store'])->name('admin.categories.store');
    Route::get('/categories/{id}/edit', [CategoriesController::class, 'edit'])->name('admin.categories.edit');
    Route::get('/categories/{id}/search', [CategoriesController::class, 'search'])->name('admin.categories.search');
    Route::match(['put', 'post'], '/categories/{id}', [CategoriesController::class, 'update'])->name('admin.categories.update');
    Route::delete('/categories/{id}', [CategoriesController::class, 'destroy'])->name('admin.categories.destroy');
    Route::post('/categories/{id}/merge', [CategoriesController::class, 'merge'])->name('admin.categories.merge');
    Route::post('/categories/bulk-merge', [CategoriesController::class, 'bulkMerge'])->name('admin.categories.bulk-merge');
    Route::post('/categories/bulk-delete', [CategoriesController::class, 'bulkDelete'])->name('admin.categories.bulk-delete');
    Route::post('/categories/{id}/aliases', [CategoriesController::class, 'addAlias'])->name('admin.categories.add-alias');
    Route::delete('/categories/aliases/{aliasId}', [CategoriesController::class, 'removeAlias'])->name('admin.categories.remove-alias');
    
    // Locations
    Route::get('/locations', [LocationsController::class, 'index'])->name('admin.locations.index');
    Route::get('/locations/create', [LocationsController::class, 'create'])->name('admin.locations.create');
    Route::post('/locations', [LocationsController::class, 'store'])->name('admin.locations.store');
    Route::get('/locations/{id}/edit', [LocationsController::class, 'edit'])->name('admin.locations.edit');
    Route::put('/locations/{id}', [LocationsController::class, 'update'])->name('admin.locations.update');
    Route::delete('/locations/{id}', [LocationsController::class, 'destroy'])->name('admin.locations.destroy');
    
    // Blogs
    Route::get('/blogs', [BlogManagementController::class, 'index'])->name('admin.blogs.index');
    Route::get('/blogs/create', [BlogManagementController::class, 'create'])->name('admin.blogs.create');
    Route::post('/blogs', [BlogManagementController::class, 'store'])->name('admin.blogs.store');
    Route::get('/blogs/{id}/edit', [BlogManagementController::class, 'edit'])->name('admin.blogs.edit');
    Route::post('/blogs/{id}', [BlogManagementController::class, 'update'])->name('admin.blogs.update');
    Route::patch('/blogs/{id}/quick-update', [BlogManagementController::class, 'quickUpdate'])->name('admin.blogs.quick-update');
    Route::delete('/blogs/{id}', [BlogManagementController::class, 'destroy'])->name('admin.blogs.destroy');
    
    // Research
    Route::get('/research', [ResearchManagementController::class, 'index'])->name('admin.research.index');
    Route::get('/research/create', [ResearchManagementController::class, 'create'])->name('admin.research.create');
    Route::post('/research', [ResearchManagementController::class, 'store'])->name('admin.research.store');
    Route::get('/research/{id}/edit', [ResearchManagementController::class, 'edit'])->name('admin.research.edit');
    Route::post('/research/{id}', [ResearchManagementController::class, 'update'])->name('admin.research.update');
    Route::patch('/research/{id}/quick-update', [ResearchManagementController::class, 'quickUpdate'])->name('admin.research.quick-update');
    Route::delete('/research/{id}', [ResearchManagementController::class, 'destroy'])->name('admin.research.destroy');
    
    // Educational Guides + Education Posts removed — content in Encyclopedia + Blog
    
    // Encyclopedia Entries
    Route::get('/encyclopedia-entries', [EncyclopediaEntriesManagementController::class, 'index'])->name('admin.encyclopedia-entries.index');
    Route::get('/encyclopedia-entries/create', [EncyclopediaEntriesManagementController::class, 'create'])->name('admin.encyclopedia-entries.create');
    Route::post('/encyclopedia-entries', [EncyclopediaEntriesManagementController::class, 'store'])->name('admin.encyclopedia-entries.store');
    Route::get('/encyclopedia-entries/{id}/edit', [EncyclopediaEntriesManagementController::class, 'edit'])->name('admin.encyclopedia-entries.edit');
    Route::post('/encyclopedia-entries/{id}', [EncyclopediaEntriesManagementController::class, 'update'])->name('admin.encyclopedia-entries.update');
    Route::patch('/encyclopedia-entries/{id}/quick-update', [EncyclopediaEntriesManagementController::class, 'quickUpdate'])->name('admin.encyclopedia-entries.quick-update');
    Route::delete('/encyclopedia-entries/{id}', [EncyclopediaEntriesManagementController::class, 'destroy'])->name('admin.encyclopedia-entries.destroy');
    Route::post('/encyclopedia-entries/{id}/toggle-visibility', [EncyclopediaEntriesManagementController::class, 'toggleVisibility'])->name('admin.encyclopedia-entries.toggle-visibility');
    
    // Pages
    Route::get('/pages', [PagesController::class, 'index'])->name('admin.pages.index');
    Route::get('/pages/create', [PagesController::class, 'create'])->name('admin.pages.create');
    Route::post('/pages', [PagesController::class, 'store'])->name('admin.pages.store');
    Route::get('/pages/{id}/edit', [PagesController::class, 'edit'])->name('admin.pages.edit');
    Route::put('/pages/{id}', [PagesController::class, 'update'])->name('admin.pages.update');
    Route::delete('/pages/{id}', [PagesController::class, 'destroy'])->name('admin.pages.destroy');
    
    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings');
    Route::post('/settings/general', [SettingsController::class, 'updateGeneral'])->name('admin.settings.general.update');
    Route::post('/settings/seo-pages', [SettingsController::class, 'upsertSeoPage'])->name('admin.settings.seo-pages.upsert');
    Route::delete('/settings/seo-pages/{seoPage}', [SettingsController::class, 'destroySeoPage'])->name('admin.settings.seo-pages.destroy');
    
    // Reviews
    Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewsController::class, 'index'])->name('admin.reviews.index');
    Route::post('/reviews/{id}/approve', [\App\Http\Controllers\Admin\ReviewsController::class, 'approve'])->name('admin.reviews.approve');
    Route::post('/reviews/{id}/reject', [\App\Http\Controllers\Admin\ReviewsController::class, 'reject'])->name('admin.reviews.reject');
    Route::delete('/reviews/{id}', [\App\Http\Controllers\Admin\ReviewsController::class, 'destroy'])->name('admin.reviews.destroy');
    
    // Deals
    Route::get('/deals', [\App\Http\Controllers\Admin\DealsController::class, 'index'])->name('admin.deals.index');
    Route::post('/deals', [\App\Http\Controllers\Admin\DealsController::class, 'store'])->name('admin.deals.store');
    Route::put('/deals/{id}', [\App\Http\Controllers\Admin\DealsController::class, 'update'])->name('admin.deals.update');
    Route::delete('/deals/{id}', [\App\Http\Controllers\Admin\DealsController::class, 'destroy'])->name('admin.deals.destroy');
    
    // Homepage hero-slide banner management
    Route::get('/banners', [\App\Http\Controllers\Admin\BannersController::class, 'index'])->name('admin.banners.index');
    Route::post('/banners/hero-slides', [\App\Http\Controllers\Admin\BannersController::class, 'updateHeroSlides'])->name('admin.banners.hero-slides.update');
    
    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('admin.analytics.index');
    
    // Users
    Route::get('/users', [\App\Http\Controllers\Admin\UsersController::class, 'index'])->name('admin.users.index');
    Route::get('/users/{id}/edit', [\App\Http\Controllers\Admin\UsersController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'update'])->name('admin.users.update');
    
    // Content
    Route::get('/content', [\App\Http\Controllers\Admin\ContentController::class, 'index'])->name('admin.content.index');
    
    // Staged / scraped products review queue
    Route::get('/staged-products', [\App\Http\Controllers\Admin\StagedProductsController::class, 'index'])->name('admin.staged-products.index');
    Route::post('/staged-products/{stagedProduct}/promote', [\App\Http\Controllers\Admin\StagedProductsController::class, 'promote'])->name('admin.staged-products.promote');
    Route::post('/staged-products/{stagedProduct}/reject', [\App\Http\Controllers\Admin\StagedProductsController::class, 'reject'])->name('admin.staged-products.reject');
    Route::post('/staged-products/bulk-promote', [\App\Http\Controllers\Admin\StagedProductsController::class, 'bulkPromote'])->name('admin.staged-products.bulk-promote');
    Route::post('/staged-products/bulk-reject', [\App\Http\Controllers\Admin\StagedProductsController::class, 'bulkReject'])->name('admin.staged-products.bulk-reject');

    // Product Scraping
    Route::get('/product-scraping', [\App\Http\Controllers\Admin\ProductScrapingController::class, 'index'])->name('admin.product-scraping.index');
    Route::post('/product-scraping/{id}/toggle', [\App\Http\Controllers\Admin\ProductScrapingController::class, 'toggle'])->name('admin.product-scraping.toggle');
    Route::post('/product-scraping/{id}/scrape', [\App\Http\Controllers\Admin\ProductScrapingController::class, 'scrape'])->name('admin.product-scraping.scrape');
    Route::post('/product-scraping/{id}/edit', [\App\Http\Controllers\Admin\ProductScrapingController::class, 'edit'])->name('admin.product-scraping.edit');
    Route::post('/product-scraping/products/{id}/toggle-override', [\App\Http\Controllers\Admin\ProductScrapingController::class, 'toggleOverride'])->name('admin.product-scraping.toggle-override');
    Route::post('/product-scraping/configs', [ScrapingConfigController::class, 'store']);
    Route::put('/product-scraping/configs/{id}', [ScrapingConfigController::class, 'update'])->name('admin.product-scraping.update');
});

// Stop impersonation — outside admin middleware so vendor-role users can hit it
Route::middleware('auth')->get('/admin/stop-impersonating', function () {
    $adminId = session('impersonating_from');
    if ($adminId) {
        auth()->login(\App\Models\User::findOrFail($adminId));
        session()->forget('impersonating_from');
    }
    return redirect('/admin/vendors');
})->name('admin.stop-impersonate');

// Authentication routes
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// Admin authentication routes
Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store']);
Route::middleware('auth')->group(function () {
    Route::post('/admin/logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('admin.logout');
});

// Vendor authentication routes
Route::post('/vendor/login', [VendorAuthenticatedSessionController::class, 'store']);
Route::middleware('auth')->group(function () {
    Route::post('/vendor/logout', [VendorAuthenticatedSessionController::class, 'destroy'])->name('vendor.logout');
});

// /vendors now serves the modern Brands page (same controller as /brands)
// Vendor integration docs — technical guide for vendor IT teams hooking
// their catalog into Peptidemap. Must be registered BEFORE /vendors so
// Laravel doesn't try to match /vendors/integration against the index route.
Route::get('/vendors/integration', [\App\Http\Controllers\Frontend\VendorIntegrationController::class, 'show'])
    ->name('vendors.integration');

// Vendor Badge Widget. SVG is served from /badge/{slug}.svg (cached, CORS-open
// so vendor sites can embed <img>). The "grab your badge" instructions page
// lives at /for-vendors/badge/{slug?}.
Route::get('/badge/{slug}', [\App\Http\Controllers\Frontend\VendorBadgeController::class, 'svg'])
    ->where('slug', '[a-zA-Z0-9\-\.]+')
    ->name('vendor.badge.svg');
Route::get('/for-vendors/badge/{slug?}', [\App\Http\Controllers\Frontend\VendorBadgeController::class, 'show'])
    ->where('slug', '[a-zA-Z0-9\-]+')
    ->name('vendor.badge');

Route::get('/vendors', [BrandsController::class, 'index'])->name('vendors.public');
// /shop/{slug} redirects to the rich brand products page
Route::get('/shop/{vendor_name}', function ($vendor_name) {
    return redirect("/brand/{$vendor_name}", 301);
})->name('shop.public');
Route::get('/product/{id}/{slug}', [\App\Http\Controllers\ProductController::class, 'show'])->name('product.public');

// Banner-slot analytics — generic; any slot can be tracked whether or not it maps to a Banner row.
Route::post('/api/banner-events/impressions', [\App\Http\Controllers\Api\BannerEventController::class, 'impressions']);
Route::post('/api/banner-events/click', [\App\Http\Controllers\Api\BannerEventController::class, 'click']);

// Discord bot JSON API — bearer-token protected via BotApiAuth middleware.
// Consumed only by the Node bot running on the same droplet.
Route::middleware('bot.api')->prefix('api/bot')->group(function () {
    Route::get('/health', [\App\Http\Controllers\Api\BotController::class, 'health']);
    Route::get('/products/search', [\App\Http\Controllers\Api\BotController::class, 'search']);
    Route::get('/vendors', [\App\Http\Controllers\Api\BotController::class, 'vendors']);
    Route::get('/compare', [\App\Http\Controllers\Api\BotController::class, 'compare']);
    Route::get('/price-drops', [\App\Http\Controllers\Api\BotController::class, 'priceDrops']);
    Route::get('/new-products', [\App\Http\Controllers\Api\BotController::class, 'newProducts']);
    Route::get('/reviews', [\App\Http\Controllers\Api\BotController::class, 'reviews']);
    Route::get('/blog-of-day', [\App\Http\Controllers\Api\BotController::class, 'blogOfDay']);
    Route::get('/promo-codes', [\App\Http\Controllers\Api\BotController::class, 'promoCodes']);
});

// Vendor Push API — Path 2 on /vendors/integration. Bearer-token auth via
// VendorApiAuth middleware; token identifies the brand. Vendors push their
// live catalog and we upsert by (brand_id, external_id).
Route::middleware(['vendor.api', 'throttle:60,1'])->prefix('api/vendor')->group(function () {
    Route::get('/products', [\App\Http\Controllers\Api\VendorProductsController::class, 'index']);
    Route::post('/products', [\App\Http\Controllers\Api\VendorProductsController::class, 'store']);
    Route::post('/products/bulk', [\App\Http\Controllers\Api\VendorProductsController::class, 'bulk']);
    Route::delete('/products/{external_id}', [\App\Http\Controllers\Api\VendorProductsController::class, 'destroy'])
        ->where('external_id', '.*');
});

// Catch-all route for any other page slugs (must be last to avoid conflicts with other routes)
// This allows creating new pages dynamically without adding routes
Route::get('/{slug}', [FrontendPagesController::class, 'show'])->name('page.show')->where('slug', '[a-z0-9-]+');
