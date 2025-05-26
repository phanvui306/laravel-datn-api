<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SanPham;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $productId = $request->productId;
        $quantity = $request->quantity;

        if (!$productId || !is_numeric($quantity) || $quantity <= 0) {
            return response()->json(['error' => 'Thiếu productId hoặc quantity không hợp lệ'], 400);
        }

        try {
            $product = SanPham::find($productId);
            if (!$product) {
                return response()->json(['error' => 'Không tìm thấy sản phẩm'], 404);
            }
            if ($product->trangthai === 'hết hàng' || $product->soluong == 0) {
                return response()->json(['error' => 'Sản phẩm này đã hết hàng, bạn vui lòng chọn sản phẩm khác'], 400);
            }

            $cart = session('cart', []);

            $existingIndex = collect($cart)->search(function ($item) use ($productId) {
                return $item['product']['id'] == $productId;
            });

            $currentQuantityInCart = $existingIndex !== false ? $cart[$existingIndex]['quantity'] : 0;
            $totalQuantity = $currentQuantityInCart + $quantity;

            if ($totalQuantity > $product->soluong) {
                return response()->json([
                    'error' => "Sản phẩm chỉ còn {$product->soluong} cái. Bạn đã có {$currentQuantityInCart} trong giỏ."
                ], 400);
            }

            if ($existingIndex !== false) {
                $cart[$existingIndex]['quantity'] += $quantity;
            } else {
                $cart[] = [
                    'product' => [
                        'id' => $product->id,
                        'tensanpham' => $product->tensanpham,
                        'gia' => $product->gia,
                        'hinh' => $product->hinh,
                    ],
                    'quantity' => $quantity,
                ];
            }

            session(['cart' => $cart]);

            return response()->json(['message' => 'Đã thêm vào giỏ hàng', 'cart' => $cart]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Đã xảy ra lỗi trên server. Vui lòng thử lại sau.'], 500);
        }
    }
    public function update(Request $request)
    {
        $productId = $request->productId;
        $quantity = $request->quantity;

        $cart = session('cart', []);

        if (empty($cart)) {
            return response()->json(['error' => 'Giỏ hàng trống'], 400);
        }

        $index = collect($cart)->search(function ($item) use ($productId) {
            return $item['product']['id'] == $productId;
        });

        if ($index === false) {
            return response()->json(['error' => 'Sản phẩm không có trong giỏ hàng'], 404);
        }

        if ($quantity <= 0) {
            return response()->json(['error' => 'Số lượng không hợp lệ'], 400);
        }

        try {
            $product = SanPham::find($productId);

            if (!$product) {
                return response()->json(['error' => 'Sản phẩm không tồn tại trong hệ thống'], 404);
            }

            if ($quantity > $product->soluong) {
                return response()->json(['error' => 'Số lượng vượt quá số lượng có sẵn'], 400);
            }

            if ($product->soluong == 0 && $product->trangthai === 'hết hàng') {
                return response()->json(['error' => 'Sản phẩm này đã hết hàng, bạn vui lòng chọn sản phẩm khác'], 400);
            }

            $cart[$index]['quantity'] = $quantity;

            session(['cart' => $cart]);

            return response()->json(['message' => 'Cập nhật số lượng thành công', 'cart' => $cart]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Đã xảy ra lỗi trên server. Vui lòng thử lại sau.'], 500);
        }
    }
    public function show()
    {
        $cart = session('cart', []);
        return response()->json(['cart' => $cart]);
    }
    public function remove(Request $request)
    {
        $productId = $request->productId;
        $cart = session('cart', []);

        if (empty($cart)) {
            return response()->json(['error' => 'Giỏ hàng trống'], 400);
        }

        $cart = collect($cart)->filter(function ($item) use ($productId) {
            return $item['product']['id'] != $productId;
        })->values()->all();

        session(['cart' => $cart]);

        return response()->json(['message' => 'Đã xóa sản phẩm khỏi giỏ hàng', 'cart' => $cart]);
    }
}
