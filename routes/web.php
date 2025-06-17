<?php

use Illuminate\Support\Facades\Route;

use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;

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
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard');
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
