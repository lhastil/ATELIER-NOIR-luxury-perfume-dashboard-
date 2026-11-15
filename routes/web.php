<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StoreSettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// Every admin page sits behind login.
Route::middleware('auth')->group(function () {
    // Open to every role: their own profile, search, notifications and logging out.
    // Search and notifications only include the sections the user may open.
    Route::get('/search', SearchController::class)->name('search');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    // The controller checks the notification type's section gate itself.
    Route::post('/notifications/{type}/{id}/read', [NotificationController::class, 'read'])
        ->whereIn('type', ['order', 'customer', 'message'])
        ->whereNumber('id')
        ->name('notifications.read');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::put('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Each section requires its gate (see User::SECTION_ROLES); other roles get 403 Forbidden.
    Route::get('/', DashboardController::class)->name('dashboard')->middleware('can:access-dashboard');

    Route::middleware('can:access-products')->group(function () {
        Route::resource('products', ProductController::class)->except('show');
    });

    Route::middleware('can:access-categories')->group(function () {
        Route::resource('categories', CategoryController::class)->except('show');
    });

    Route::middleware('can:access-orders')->group(function () {
        Route::resource('orders', OrderController::class)->only(['index', 'show']);
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status.update');
    });

    Route::middleware('can:access-customers')->group(function () {
        Route::resource('customers', CustomerController::class)->only(['index', 'show']);
    });

    Route::middleware('can:access-messages')->group(function () {
        Route::resource('messages', MessageController::class)->only(['index', 'show', 'destroy']);
        Route::patch('/messages/{message}/status', [MessageController::class, 'updateStatus'])->name('messages.status.update');
    });

    Route::middleware('can:access-settings')->group(function () {
        Route::get('/settings', [StoreSettingController::class, 'edit'])->name('settings.edit');
        Route::patch('/settings', [StoreSettingController::class, 'update'])->name('settings.update');
    });

    Route::middleware('can:access-users')->group(function () {
        Route::resource('users', UserController::class)->except('show');
    });
});
