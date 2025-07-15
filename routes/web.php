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
use App\Http\Controllers\Vendor\ImportController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Auth\EmailVerificationController;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

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

Route::get('/shop/{vendor_name}', [PublicVendorController::class, 'show'])->name('shop.public');
Route::get('/product/{id}/{slug}', [\App\Http\Controllers\ProductController::class, 'show'])->name('product.public');
