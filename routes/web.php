<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\ShopifyAuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// User Registration Routes
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

// Protected Routes (require authentication)
Route::middleware(['web', 'simple.auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard/data', [DashboardController::class, 'getDashboardData'])->name('dashboard.data');
    
    // Products Routes
    Route::get('/products', [DashboardController::class, 'products'])->name('products.index');
    Route::get('/products/export', [ExportController::class, 'exportProducts'])->name('products.export');
    
    // Orders Routes
    Route::get('/orders', [DashboardController::class, 'orders'])->name('orders.index');
    Route::get('/orders/export', [ExportController::class, 'exportOrders'])->name('orders.export');
    
    // Export History Routes
    Route::get('/exports/history', [ExportController::class, 'exportHistory'])->name('export.history');
    
    // Log Viewer Routes
    Route::get('/logs', [LogViewerController::class, 'index'])->name('logs.index');
    Route::post('/logs/clear', [LogViewerController::class, 'clear'])->name('logs.clear');
    Route::get('/logs/download', [LogViewerController::class, 'download'])->name('logs.download');
    
    // Shopify OAuth Routes
    Route::get('/shopify/connect', [ShopifyAuthController::class, 'showConnect'])->name('shopify.connect');
    Route::match(['GET', 'POST'], '/shopify/authorize', [ShopifyAuthController::class, 'redirectToShopify'])->name('shopify.authorize');
    Route::post('/shopify/disconnect', [ShopifyAuthController::class, 'disconnect'])->name('shopify.disconnect');
    
});

// Shopify OAuth Callback (outside auth middleware)
Route::get('/shopify/callback', [ShopifyAuthController::class, 'callback'])->name('shopify.callback');
