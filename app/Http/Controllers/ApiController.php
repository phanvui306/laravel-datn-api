<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KhachHang;
use App\Models\SanPham;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;
use App\Models\Voucher;
use App\Models\DanhMuc;

class ApiController extends Controller
{
    public function getAllUsers()
    {
        try {
            return response()->json(KhachHang::all(), 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server'], 500);
        }
    }
    public function searchUsers(Request $request)
    {
        try {
            $keyword = $request->query('keyword');
            $users = KhachHang::where('hoten', 'like', "%$keyword%")
                ->orWhere('dienthoai', 'like', "%$keyword%")
                ->get();

            return response()->json($users, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server'], 500);
        }
    }
    public function getAllProducts()
    {
        try {
            return response()->json(SanPham::all(), 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server'], 500);
        }
    }
    public function searchProducts(Request $request)
    {
        try {
            $keyword = $request->query('keyword');
            $query = SanPham::query();

            if ($keyword) {
                $query->where('tensanpham', 'like', "%$keyword%");
            }

            return response()->json($query->get(), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Có lỗi xảy ra khi tìm kiếm sản phẩm'], 500);
        }
    }
    public function getAllOrders()
    {
        try {
            $orders = DonHang::orderBy('ngay', 'desc')->get();
            return response()->json($orders);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Đã có lỗi xảy ra khi lấy đơn hàng'], 500);
        }
    }
    public function searchOrders(Request $request)
    {
        try {
            $keyword = $request->query('keyword');
            $orders = DonHang::where('dienthoai', 'like', "%$keyword%")->get();
            return response()->json($orders, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server'], 500);
        }
    }
    public function getAllCategories()
    {
        try {
            $categories = DanhMuc::orderBy('id', 'asc')->get();
            return response()->json($categories);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Đã có lỗi xảy ra khi lấy danh mục'], 500);
        }
    }
    public function searchCategories(Request $request)
    {
        try {
            $keyword = $request->query('keyword');
            $categories = DanhMuc::where('tendanhmuc', 'like', "%$keyword%")->get();
            return response()->json($categories, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server'], 500);
        }
    }
    public function getAllVouchers()
    {
        try {
            return response()->json(Voucher::all(), 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server'], 500);
        }
    }
    public function searchVouchers(Request $request)
    {
        try {
            $keyword = $request->query('keyword');
            $vouchers = Voucher::where('giagiam', 'like', "%$keyword%")->get();
            return response()->json($vouchers, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server'], 500);
        }
    }
    public function show($id)
    {
        $user = KhachHang::find($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);
        return response()->json($user);
    }
    public function update(Request $request, $id)
    {
        $user = KhachHang::find($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);

        $user->hoten = $request->hoten;
        $user->dienthoai = $request->dienthoai;
        $user->email = $request->email;
        $user->diachi = $request->diachi;
        $user->password = $request->password; // Có thể cần mã hóa nếu là mật khẩu
        $user->role = $request->role;
        $user->save();

        return response()->json(['message' => 'Cập nhật thành công']);
    }
    public function store(Request $request)
    {
        $email = $request->input('email');
        $phone = $request->input('dienthoai');
    
        // Kiểm tra email
        if (KhachHang::where('email', $email)->exists()) {
            return response()->json([
                'message' => 'Email đã tồn tại.'
            ], 422);
        }
    
        // Kiểm tra số điện thoại
        if (KhachHang::where('dienthoai', $phone)->exists()) {
            return response()->json([
                'message' => 'Số điện thoại đã tồn tại.'
            ], 422);
        }
    
        $user = new KhachHang();
        $user->hoten     = $request->input('hoten');
        $user->dienthoai = $phone;
        $user->email     = $email;
        $user->diachi    = $request->input('diachi', '');
        $user->password  = $request->input('password'); // mã hóa mật khẩu
        $user->role      = $request->input('role');
        $user->save();
    
        return response()->json([
            'message' => 'Tạo tài khoản thành công!',
            'user'    => $user
        ], 201);
    }
}
