<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Vendor\VendorController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Redis;
 
use App\Http\Controllers\ChatbotController;
use Inertia\Inertia;

// Route::get('/', function () {
//         return Inertia::render('Welcome');
//     });          

Route::get('/', function () {

    if (!Auth::check()) {
        return redirect()->route('login');
    }
    $user = Auth::user();
    if ($user->role === 'administrator') {
        return redirect()->intended(route('dashboard'));
    }
    if ($user->role === 'vendor') {
        return redirect()->intended(route('vendor.dashboard'));
    }
    return redirect()->intended('/');
});

Route::get('login', [LoginController::class, 'create'])->name('login');
Route::post('login', [LoginController::class, 'store']);

Route::get('register', [RegisterController::class, 'create'])->name('register');
Route::post('register', [RegisterController::class, 'register'])->name('register');

// **********************forget password********************

Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
    ->middleware('guest')
    ->name('password.email');

Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [PasswordResetController::class, 'updatePassword'])
    ->middleware('guest')
    ->name('password.update');


Route::prefix('vendor')
    ->name('vendor.')
    ->middleware(['auth', 'role:vendor'])
    ->group(function () {

        Route::get('/dashboard', [VendorController::class, 'vendordash'])
            ->name('dashboard');

        Route::get('/products', [VendorController::class, 'vendorproduct'])
            ->name('products');

        Route::post('/products/store', [VendorController::class, 'store'])
            ->name('products.store');

        Route::get('/orders', [VendorController::class, 'vendororders'])
            ->name('orders');

        Route::post('/products/update/{id}', [VendorController::class, 'update'])
            ->name('products.update');

        Route::delete('/products/{id}', [VendorController::class, 'destroy'])
            ->name('products.destroy');
        Route::put('/profile/update', [VendorController::class, 'update_profile'])->name('profile.update');

        Route::get('/reports/products', [VendorController::class, 'reports_products'])
            ->name('reports.products');

        Route::get('/import_product', [VendorController::class, 'import_product'])->name('import_product');

        Route::post('/products/import', [VendorController::class, 'import'])->name('products.import');
        Route::post('/products/{id}/sync-shopify', [VendorController::class, 'syncToShopify'])->name('products.sync');
});



Route::middleware(['auth', 'role:administrator'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.index');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('product.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('product.destroy');
        

    Route::get('/category', [CategoryController::class, 'index'])->name('category.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('category.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('category.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('category.destroy');
    Route::put('/profile/update', [ProfileController::class, 'update']);

    Route::get('/orders', [DashboardController::class, 'orders'])->name('orders');
    Route::get('/customer', [DashboardController::class, 'getcustomer'])->name('customer');
    Route::delete('/users/{id}', [DashboardController::class, 'destroy_user'])->name('users.destroy');
    Route::put('/users/{id}', [DashboardController::class, 'update_user'])->name('users.update');
    Route::post('users', [CustomerController::class, 'addnewuser'])->name('users');
    Route::post('/admin/chatbot/query', [ChatbotController::class, 'handleQuery']);
    Route::post('/client-import', [CustomerController::class, 'store']);
});

// Route::post('/webhook/product',[ProductController::class,'webhook_create_products'])->name('webhook.product');
Route::post('/webhook/update', [ProductController::class, 'webhook_update_products'])->name('webhook.update');
Route::post('/webhook/delete', [ProductController::class, 'webhook_delete_products'])->name('webhook.delete');
Route::get('/getorders',[OrderController::class,'index'])->name('getorders');  

Route::post('/logout', function () {

    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/login');
})->name('logout');




Route::post('/webhook/product', [ProductController::class, 'webhook_create_products'])
    ->name('webhook.product');

