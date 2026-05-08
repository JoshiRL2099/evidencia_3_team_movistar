<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// ── Rutas públicas ───────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── Rutas protegidas (requieren login) ───────────────────────────
Route::middleware(['auth'])->group(function () {
    // Dashboard: accessible to authenticated users (will render per-role dashboards)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- Admin-only routes -------------------------------------------------
    // Admin: acceso total, administración de usuarios, roles y productos
    Route::middleware(['role:ADMIN'])->group(function () {
        Route::resource('roles', RoleController::class);
        Route::resource('users', UserController::class);
        Route::resource('products', ProductController::class);

        // Admin-only order actions: delete (soft), view trash and restore
        Route::delete('/orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');
        Route::get('/orders-trash', [OrderController::class, 'trash'])->name('orders.trash');
        Route::post('/orders/{id}/restore', [OrderController::class, 'restore'])->name('orders.restore');
    });

    // --- Customers: Admin + Sales ------------------------------------------
    // Admin and Sales can manage customers
    Route::middleware(['role:ADMIN,SALES'])->group(function () {
        Route::resource('customers', CustomerController::class);
    });

    // --- Orders: per-action role protection ---------------------------------
    // Index & show: all roles that interact with orders can view
    Route::get('/orders', [OrderController::class, 'index'])
        ->name('orders.index')
        ->middleware('role:ADMIN,SALES,WAREHOUSE,PURCHASING,ROUTE');

    Route::get('/orders/create', [OrderController::class, 'create'])
        ->name('orders.create')
        ->middleware('role:ADMIN,SALES');

    Route::post('/orders', [OrderController::class, 'store'])
        ->name('orders.store')
        ->middleware('role:ADMIN,SALES');

    // Edit & full update: only Admin and Sales
    Route::get('/orders/{id}/edit', [OrderController::class, 'edit'])
        ->name('orders.edit')
        ->middleware('role:ADMIN,SALES');

    Route::put('/orders/{id}', [OrderController::class, 'update'])
        ->name('orders.update')
        ->middleware('role:ADMIN,SALES');

    // Status-only updates: Admin, Warehouse and Route can change status via dedicated endpoint
    Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus'])
        ->name('orders.update.status')
        ->middleware('role:ADMIN,WAREHOUSE,ROUTE');

    Route::get('/orders/{id}', [OrderController::class, 'show'])
        ->name('orders.show')
        ->middleware('role:ADMIN,SALES,WAREHOUSE,PURCHASING,ROUTE');

    // Upload evidence photos for an order (only internal, authenticated users)
    Route::post('/orders/{id}/photos', [\App\Http\Controllers\PhotoController::class, 'store'])
        ->name('orders.photos.store')
        ->middleware('role:ADMIN,ROUTE');
});