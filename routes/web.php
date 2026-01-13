<?php

use Illuminate\Support\Facades\Route;

use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use App\Http\Controllers\Vendor\VendorSettingsController;
use App\Http\Controllers\Vendor\PublicVendorController;
use App\Http\Controllers\Admin\VendorsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\BlogManagementController;
use App\Http\Controllers\Admin\EducationPostsController;
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
use App\Http\Controllers\Frontend\BlogsController;
use App\Http\Controllers\Frontend\EducationController;
use App\Http\Controllers\Frontend\VendorReviewsController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PagesController as FrontendPagesController;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Frontend pages
Route::get('/products', [ProductsController::class, 'index'])->name('products');
Route::get('/product/{slug}', [ProductsController::class, 'show'])->name('product.show');
Route::get('/brand/{slug}/products', [ProductsController::class, 'byBrand'])->name('brand.products');
Route::get('/brands', [BrandsController::class, 'index'])->name('brands');
Route::get('/blogs', [BlogsController::class, 'index'])->name('blogs');
Route::get('/blog/{slug}', [BlogsController::class, 'show'])->name('blog.show');
Route::get('/education', [EducationController::class, 'index'])->name('education');
Route::get('/education/{slug}', [EducationController::class, 'show'])->name('education.show');

// Vendor Reviews
Route::post('/brands/{brandId}/reviews', [VendorReviewsController::class, 'store'])->name('vendor.reviews.store');
Route::get('/brands/{brandId}/reviews', [VendorReviewsController::class, 'index'])->name('vendor.reviews.index');

// Catch-all route for any other page slugs (must be last to avoid conflicts with other routes)
// This allows creating new pages dynamically without adding routes
Route::get('/{slug}', [FrontendPagesController::class, 'show'])->name('page.show')->where('slug', '[a-z0-9-]+');

// Guest routes
Route::middleware('guest')->group(function () {
    // Vendor routes
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');

    // Admin routes
    Route::get('/admin/login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login');
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
    Route::get('/import', [ImportController::class, 'index'])->name('vendor.import');
    Route::post('/import/file', [ImportController::class, 'importFromFile'])->name('vendor.import.file');
    Route::post('/import/url', [ImportController::class, 'importFromUrl'])->name('vendor.import.url');
    Route::delete('/products/{product}', [ImportController::class, 'deleteProduct'])->name('vendor.products.delete');
    Route::get('/profile', function () {
        return Inertia::render('Vendor/Profile');
    })->name('vendor.profile');
    Route::get('/settings', [VendorSettingsController::class, 'show'])->name('vendor.settings');
    Route::post('/settings', [VendorSettingsController::class, 'update'])->name('vendor.settings.update');
});

// Admin routes
Route::middleware(['auth', 'role:admin', 'email.verified'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/vendors', [VendorsController::class, 'index'])->name('admin.vendors');
    Route::get('/vendors/create', [VendorsController::class, 'create'])->name('admin.vendors.create');
    Route::post('/vendors', [VendorsController::class, 'store'])->name('admin.vendors.store');
    Route::get('/vendors/{id}/edit', [VendorsController::class, 'edit'])->name('admin.vendors.edit');
    Route::post('/vendors/{id}', [VendorsController::class, 'update'])->name('admin.vendors.update');
    Route::delete('/vendors/{id}', [VendorsController::class, 'destroy'])->name('admin.vendors.destroy');
    Route::post('/vendors/{id}/toggle-status', [VendorsController::class, 'toggleStatus'])->name('admin.vendors.toggle-status');
    Route::get('/vendors/{id}/products', [VendorsController::class, 'products'])->name('admin.vendors.products');
    Route::post('/vendors/{id}/products/import', [VendorsController::class, 'importProductsFromFile'])->name('admin.vendors.products.import');
    Route::post('/vendors/{id}/products/import-url', [VendorsController::class, 'importProductsFromUrl'])->name('admin.vendors.products.import-url');
    Route::post('/vendors/{id}/import-shop-url', [VendorsController::class, 'importFromShopUrl'])->name('admin.vendors.import-shop-url');
    Route::delete('/vendors/{vendorId}/products/{productId}', [VendorsController::class, 'deleteProduct'])->name('admin.vendors.products.delete');
    Route::get('/products', [VendorsController::class, 'adminProducts'])->name('admin.products');
    Route::get('/products/{id}/edit', [AdminProductsController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{id}', [AdminProductsController::class, 'update'])->name('admin.products.update');
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
    
    // Education Posts
    Route::get('/education-posts', [EducationPostsController::class, 'index'])->name('admin.education-posts.index');
    Route::get('/education-posts/create', [EducationPostsController::class, 'create'])->name('admin.education-posts.create');
    Route::post('/education-posts', [EducationPostsController::class, 'store'])->name('admin.education-posts.store');
    Route::get('/education-posts/{id}/edit', [EducationPostsController::class, 'edit'])->name('admin.education-posts.edit');
    Route::post('/education-posts/{id}', [EducationPostsController::class, 'update'])->name('admin.education-posts.update');
    Route::delete('/education-posts/{id}', [EducationPostsController::class, 'destroy'])->name('admin.education-posts.destroy');
    
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
    Route::post('/settings/hero-slides', [SettingsController::class, 'updateHeroSlides'])->name('admin.settings.hero-slides.update');
    
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
    
    // Banners
    Route::get('/banners', [\App\Http\Controllers\Admin\BannersController::class, 'index'])->name('admin.banners.index');
    Route::post('/banners', [\App\Http\Controllers\Admin\BannersController::class, 'store'])->name('admin.banners.store');
    Route::put('/banners/{id}', [\App\Http\Controllers\Admin\BannersController::class, 'update'])->name('admin.banners.update');
    Route::post('/banners/{id}/toggle', [\App\Http\Controllers\Admin\BannersController::class, 'toggle'])->name('admin.banners.toggle');
    Route::delete('/banners/{id}', [\App\Http\Controllers\Admin\BannersController::class, 'destroy'])->name('admin.banners.destroy');
    
    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('admin.analytics.index');
    
    // Users
    Route::get('/users', [\App\Http\Controllers\Admin\UsersController::class, 'index'])->name('admin.users.index');
    
    // Content
    Route::get('/content', [\App\Http\Controllers\Admin\ContentController::class, 'index'])->name('admin.content.index');
    
    // Product Scraping
    Route::get('/product-scraping', [\App\Http\Controllers\Admin\ProductScrapingController::class, 'index'])->name('admin.product-scraping.index');
    Route::post('/product-scraping/{id}/toggle', [\App\Http\Controllers\Admin\ProductScrapingController::class, 'toggle'])->name('admin.product-scraping.toggle');
    Route::post('/product-scraping/{id}/scrape', [\App\Http\Controllers\Admin\ProductScrapingController::class, 'scrape'])->name('admin.product-scraping.scrape');
    Route::post('/product-scraping/{id}/edit', [\App\Http\Controllers\Admin\ProductScrapingController::class, 'edit'])->name('admin.product-scraping.edit');
    Route::post('/product-scraping/products/{id}/toggle-override', [\App\Http\Controllers\Admin\ProductScrapingController::class, 'toggleOverride'])->name('admin.product-scraping.toggle-override');
    Route::post('/product-scraping/configs', [ScrapingConfigController::class, 'store']);
    Route::put('/product-scraping/configs/{id}', [ScrapingConfigController::class, 'update'])->name('admin.product-scraping.update');
});

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

Route::get('/vendors', [PublicVendorController::class, 'list'])->name('vendors.public');
Route::get('/shop/{vendor_name}', [PublicVendorController::class, 'show'])->name('shop.public');
Route::get('/product/{id}/{slug}', [\App\Http\Controllers\ProductController::class, 'show'])->name('product.public');
