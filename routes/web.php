<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminOrderReportController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MidtransPaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/midtrans/notification', [MidtransPaymentController::class, 'notification'])->name('payments.midtrans.notification');

Route::get('/', [ProductController::class, 'home'])->name('home');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::middleware('auth')->group(function (): void {
    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/products/{product}/cart-items', [CartController::class, 'store'])->name('cart-items.store');
    Route::patch('/cart-items/{cartItem}', [CartController::class, 'update'])->name('cart-items.update');
    Route::delete('/cart-items/{cartItem}', [CartController::class, 'destroy'])->name('cart-items.destroy');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/checkout', [OrderController::class, 'create'])->name('checkout.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order:order_code}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order:order_code}/payment-proofs', [PaymentProofController::class, 'store'])->middleware('throttle:10,1')->name('orders.payment-proofs.store');
    Route::get('/orders/{order:order_code}/payment-proofs/{paymentProof}', [PaymentProofController::class, 'show'])->scopeBindings()->name('orders.payment-proofs.show');
    Route::post('/orders/{order:order_code}/midtrans/session', [MidtransPaymentController::class, 'store'])->middleware('throttle:10,1')->name('orders.midtrans.session');
    Route::post('/orders/{order:order_code}/midtrans/sync', [MidtransPaymentController::class, 'sync'])->middleware('throttle:10,1')->name('orders.midtrans.sync');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', AdminUserController::class);
    Route::resource('products', AdminProductController::class);
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order:order_code}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order:order_code}/manual-payment', [AdminPaymentController::class, 'update'])->name('orders.manual-payment.update');
    Route::patch('/orders/{order:order_code}/payment-proofs/{paymentProof}', [AdminPaymentController::class, 'review'])->scopeBindings()->name('orders.payment-proofs.update');
    Route::get('/orders/{order:order_code}/payment-proofs/{paymentProof}', [PaymentProofController::class, 'show'])->scopeBindings()->name('orders.payment-proofs.show');
    Route::post('/orders/{order:order_code}/midtrans/sync', [MidtransPaymentController::class, 'sync'])->middleware('throttle:10,1')->name('orders.midtrans.sync');
    Route::patch('/orders/{order:order_code}/status', [AdminOrderController::class, 'updateStatus'])
        ->name('orders.status');
    Route::get('/reports/orders', [AdminOrderReportController::class, 'index'])->name('reports.orders');
    Route::get('/reports/orders/download', [AdminOrderReportController::class, 'download'])
        ->name('reports.orders.download');
    Route::get('/reports/orders/download/pdf', [AdminOrderReportController::class, 'downloadPdf'])
        ->name('reports.orders.download.pdf');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
