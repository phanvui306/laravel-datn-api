<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// routes/api.php
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\SanPhamController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\VNPayController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DanhMucController;

//-- chức năng người dùng--//
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout']);
Route::get('/check-session', [UserController::class, 'checkSession']);
Route::get('/user-info', [UserController::class, 'userInfo']);
Route::post('/user/update', [UserController::class, 'updateUser']);
Route::post('/change-password', [UserController::class, 'changePassword']);
Route::post('/reset-password-simple', [UserController::class, 'resetPasswordSimple']);


Route::get('/products', [ProductController::class, 'getAllProducts']);
Route::get('/products/hot', [ProductController::class, 'getHotProducts']);
Route::get('/products/category/{iddanhmuc}', [ProductController::class, 'getProductsByCategory']);
Route::get('/products/search', [ProductController::class, 'searchProducts']);
Route::get('/products/{id}', [ProductController::class, 'getProductById']);

Route::post('/cart/add', [CartController::class, 'add']);
Route::post('/cart/update', [CartController::class, 'update']);
Route::get('/cart', [CartController::class, 'show']);
Route::post('/cart/remove', [CartController::class, 'remove']);

Route::get('/voucher', [VoucherController::class, 'getVouchers']);
Route::post('/validate-voucher', [VoucherController::class, 'validateVoucher']);

Route::post('/checkout', [CheckoutController::class, 'checkout']);
Route::post('/checkout-now', [CheckoutController::class, 'checkoutNow']);
Route::get('/orders', [CheckoutController::class, 'getOrders']);
Route::get('/donhang/{id}', [OrderController::class, 'show']);
Route::get('/vnpay/return', [CheckoutController::class, 'vnpayReturn']);
Route::get('/revenue-stats', [OrderController::class, 'getRevenueStats']);
Route::get('/dashboard-stats', [OrderController::class, 'stats']);

//-- chức năng admin--//
//api người dùng
Route::get('/userall', [ApiController::class, 'getAllUsers']);
Route::get('/usersearch', [ApiController::class, 'searchUsers']);
Route::get('/users/{id}', [ApiController::class, 'show']);
Route::put('/users/{id}', [ApiController::class, 'update']);
Route::post('/usersthem', [ApiController::class, 'store']);
//api sản phẩm
Route::get('/sanphamall', [ApiController::class, 'getAllProducts']);
Route::get('/sanphamsearch', [ApiController::class, 'searchProducts']);
Route::post('/productsthem', [SanPhamController::class, 'store']);
Route::get('/productssua/{id}', [SanPhamController::class, 'show']);
Route::post('/productssua/{id}', [SanPhamController::class, 'update']); 
Route::delete('/productsxoa/{id}', [SanPhamController::class, 'destroy']);
//api đơn hàng
Route::get('/donhangall', [ApiController::class, 'getAllOrders']);
Route::get('/donhangsearch', [ApiController::class, 'searchOrders']);
Route::put('/donhang/{id}/trangthai', [OrderController::class, 'capNhatTrangThai']);
//api danh mục
Route::get('/danhmucall', [ApiController::class, 'getAllCategories']);
Route::get('/danhmucsearch', [ApiController::class, 'searchCategories']);
Route::get('/danhmucsua/{id}', [DanhMucController::class, 'show']);
Route::post('/danhmucthem', [DanhMucController::class, 'store']);
Route::put('/danhmucsua/{id}', [DanhMucController::class, 'update']);
Route::delete('/danhmucxoa/{id}', [DanhMucController::class, 'destroy']);
//api voucher
Route::get('/voucherall', [ApiController::class, 'getAllVouchers']);
Route::get('/vouchersearch', [ApiController::class, 'searchVouchers']);
Route::delete('/voucherxoa/{id}', [VoucherController::class, 'destroy']);
Route::post('/voucherthem', [VoucherController::class, 'store']);
Route::get('/vouchersua/{id}', [VoucherController::class, 'show']);
Route::put('/vouchersua/{id}', [VoucherController::class, 'update']);





