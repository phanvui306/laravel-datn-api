<?php

namespace App\Http\Controllers;

use App\Models\SanPham;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function getAllProducts()
    {
        try {
            $products = SanPham::select('id', 'tensanpham', 'mota', 'hinh', 'iddanhmuc', 'sphot', 'gia')->get();
            return response()->json($products);
        } catch (\Exception $err) {
            \Log::error('Lỗi lấy sản phẩm: ' . $err->getMessage());
            return response()->json(['error' => 'Lỗi server'], 500);
        }
    }
    public function getHotProducts()
    {
        try {
            $hotProducts = SanPham::where('sphot', 1)
                ->select('id', 'tensanpham', 'mota', 'hinh', 'iddanhmuc', 'sphot', 'gia')
                ->get();
            return response()->json($hotProducts);
        } catch (\Exception $err) {
            return response()->json(['error' => 'Lỗi server'], 500);
        }
    }
    public function getProductsByCategory($iddanhmuc)
    {
        try {
            $products = SanPham::where('iddanhmuc', $iddanhmuc)
                ->select('id', 'tensanpham', 'mota', 'hinh', 'iddanhmuc', 'sphot', 'gia')
                ->get();
            return response()->json($products);
        } catch (\Exception $err) {
            return response()->json(['error' => 'Lỗi server'], 500);
        }
    }
    public function searchProducts(Request $request)
    {
        $q = $request->query('q');
        if (!$q) {
            return response()->json(['error' => 'Vui lòng nhập từ khóa tìm kiếm'], 400);
        }

        try {
            $products = SanPham::where('tensanpham', 'like', "%$q%")
                ->select('id', 'tensanpham', 'mota', 'hinh', 'iddanhmuc', 'sphot', 'gia')
                ->get();
            return response()->json($products);
        } catch (\Exception $err) {
            return response()->json(['error' => 'Lỗi server'], 500);
        }
    }

    public function getProductById($id)
    {
        try {
            $product = SanPham::select(
                'id',
                'tensanpham',
                'mota',
                'hinh',
                'hinh1',
                'hinh2',
                'hinh3',
                'iddanhmuc',
                'sphot',
                'gia',
                'soluong',
                'trangthai'
            )->find($id);

            if (!$product) {
                return response()->json(['error' => 'Không tìm thấy sản phẩm'], 404);
            }

            return response()->json($product);
        } catch (\Exception $err) {
            \Log::error($err->getMessage());
            return response()->json(['error' => 'Lỗi server'], 500);
        }
    }
}
