<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ProductsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UploadController;
// use App\Http\Controllers\Api\Admin\AdminSellerController;
use App\Http\Controllers\Api\V1\ServicesController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\SellerApplicationController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\SellerController as AdminSellerController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\Admin\AdminSellerApplicationController;

Route::prefix('v1')->group(function() {
    // public
    Route::get('products', [ProductsController::class,'index']);
    Route::get('products/{slug}', [ProductsController::class,'show']);
    Route::get('services', [ServicesController::class,'index']);
    Route::get('services/{slug}', [ServicesController::class,'show']);
    Route::post('orders', [OrderController::class,'store']);
    Route::get('/v1/orders/{id}', [OrderController::class, 'show']);
    Route::post('/v1/orders/{id}/pay', [OrderController::class, 'pay']);
    Route::post('seller-applications', [SellerApplicationController::class,'store']);
    Route::post('admin/login', [AuthController::class,'login']);
    Route::post('uploads', [UploadController::class, 'store']);
    Route::get('/categories', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'index']);





    // admin auth
    Route::middleware(['auth:sanctum',EnsureUserIsAdmin::class])->prefix('admin')->group(function() {
        Route::get('me', [AuthController::class,'me']);
        Route::post('logout', [AuthController::class,'logout']);
        Route::get('seller-applications', [AdminSellerApplicationController::class, 'index']);
        Route::get('seller-applications/{id}', [AdminSellerController::class, 'show']);
        Route::post('seller-applications/{id}/approve', [AdminSellerApplicationController::class, 'approve']);
        Route::get('/categories', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'index']);
        Route::post('categories', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'store']);
        Route::put('categories/{id}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'update']);
        Route::delete('categories/{id}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'destroy']);


        Route::apiResource('products', AdminProductController::class);
        // Route::post('uploads', [\App\Http\Controllers\Api\Admin\UploadController::class, 'upload']);
        // Route::post('uploads/presign', [UploadController::class,'presign']);
        Route::get('orders', [AdminOrderController::class,'index']);
        Route::patch('orders/{id}', [AdminOrderController::class,'update']);
        Route::get('sellers', [AdminSellerController::class,'index']);
        Route::post('sellers/{id}/approve', [AdminSellerController::class,'approve']);
    });
});
