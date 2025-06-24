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

// Vendor routes
Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Vendor/Dashboard');
    })->name('vendor.dashboard');
    Route::get('/profile', function () {
        return Inertia::render('Vendor/Profile');
    })->name('vendor.profile');
    Route::get('/settings', [VendorSettingsController::class, 'show'])->name('vendor.settings');
    Route::post('/settings', [VendorSettingsController::class, 'update'])->name('vendor.settings.update');
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/vendors', [VendorsController::class, 'index'])->name('admin.vendors');
    Route::post('/vendors/{id}/toggle-status', [VendorsController::class, 'toggleStatus'])->name('admin.vendors.toggle-status');
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

Route::get('/vendor/{vendor_name}', [PublicVendorController::class, 'show'])->name('vendor.public');
