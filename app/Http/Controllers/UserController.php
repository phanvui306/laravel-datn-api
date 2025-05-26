<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KhachHang;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    public function register(Request $request){
        $hoten = $request->input('hoten');
        $email = $request->input('email');
        $password = $request->input('password');
        $dienthoai = $request->input('dienthoai');
        $diachi = $request->input('diachi');

        try {
            // Kiểm tra email đã tồn tại trong cơ sở dữ liệu
            $existing = KhachHang::where('email', $email)->first();
            if ($existing) {
                return response()->json(['message' => 'Email đã được sử dụng'], 400);
            }   
            if (strlen($dienthoai) != 10) {
                return response()->json(['message' => 'Số điện thoại phải có 10 chữ số'], 400);
            }         
            $kiemtra = KhachHang::where('dienthoai', $dienthoai)->first();
            if ($kiemtra) {
                return response()->json(['message' => 'Số điện thoại đã được sử dụng'], 400);
            }

            // Tạo mới khách hàng mà không mã hóa mật khẩu
            $user = KhachHang::create([
                'hoten' => $hoten,
                'email' => $email,
                'password' => $password, // Không mã hóa mật khẩu
                'dienthoai' => $dienthoai,
                'diachi' => $diachi,
                'role' => 0, // Vai trò mặc định
            ]);

            // Trả về phản hồi giống như Node.js
            return response()->json([
                'message' => 'Đăng ký thành công',
                'user' => $user
            ], 201);

        } catch (\Exception $err) {
            // Xử lý lỗi server
            return response()->json(['error' => 'Lỗi server'], 500);
        }
    }
    public function login(Request $request) {
        $data = $request->only('email', 'password');
        $user = KhachHang::where('email', $data['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy người dùng'], 404);
        }

        if ($user->password !== $data['password']) {
            return response()->json(['message' => 'Sai mật khẩu'], 400);
        }

        session(['user_id' => $user->id]);
        session(['user' => $user->only(['id', 'hoten', 'email', 'dienthoai', 'diachi', 'role'])]);

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'user' => session('user')
        ]);
    }
    public function logout(Request $request) {
        Session::flush();
        return response()->json(['message' => 'Đăng xuất thành công']);
    }
    public function checkSession() {
        return response()->json(['loggedIn' => session()->has('user_id')]);
    }
    public function userInfo() {
        if (!session()->has('user_id')) {
            return response()->json(['loggedIn' => false]);
        }

        $user = KhachHang::find(session('user_id'));

        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy người dùng'], 404);
        }

        return response()->json(['loggedIn' => true, 'user' => $user]);
    }
    public function updateUser(Request $request) {
        $userId = session('user_id');
        if (!$userId) return response()->json(['error' => 'Chưa đăng nhập'], 401);
    
        $user = KhachHang::find($userId);
        if (!$user) return response()->json(['error' => 'Không tìm thấy người dùng'], 404);
    
        $email = $request->email;
        $dienthoai = $request->dienthoai;
    
        // Kiểm tra số điện thoại có đủ 10 số không
        if (strlen($dienthoai) != 10) {
            return response()->json(['error' => 'Số điện thoại phải có 10 chữ số'], 400);
        }
    
        // Kiểm tra email đã tồn tại trong tài khoản khác
        $existingEmailUser = KhachHang::where('email', $email)->where('id', '!=', $userId)->first();
        if ($existingEmailUser) {
            return response()->json(['error' => 'Email đã được sử dụng bởi tài khoản khác'], 400);
        }
    
        // Kiểm tra số điện thoại đã tồn tại trong tài khoản khác
        $existingPhoneUser = KhachHang::where('dienthoai', $dienthoai)->where('id', '!=', $userId)->first();
        if ($existingPhoneUser) {
            return response()->json(['error' => 'Số điện thoại đã được sử dụng bởi tài khoản khác'], 400);
        }
    
        // Cập nhật thông tin người dùng
        $user->update($request->only('hoten', 'email', 'dienthoai', 'diachi'));
    
        return response()->json(['updatedUser' => $user]);
    }
    public function changePassword(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return response()->json(['message' => 'Chưa đăng nhập'], 401);
        }
    
        $request->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|min:6',
        ]);
    
        $user = KhachHang::find($userId);
    
        if (!$user || $user->password !== $request->currentPassword) {
            return response()->json(['message' => 'Mật khẩu hiện tại không đúng'], 400);
        }
    
        $user->password = $request->newPassword;
        $user->save();
    
        return response()->json(['message' => 'Đổi mật khẩu thành công']);
    }
    public function resetPasswordSimple(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email',
            'dienthoai' => 'required',
            'password' => 'required|min:6',
        ]);
    
        // Tìm user theo email
        $user = KhachHang::where('email', $request->email)->first();
    
        // Kiểm tra nếu không tìm thấy user theo email
        if (!$user) {
            return response()->json(['error' => 'Email không khớp!'], 400);
        }
    
        // Kiểm tra nếu số điện thoại không khớp
        if ($user->dienthoai !== $request->dienthoai) {
            return response()->json(['error' => 'Số điện thoại không khớp!'], 400);
        }
    
        // Cập nhật mật khẩu
        $user->password =$request->password; // Mã hóa mật khẩu
        $user->save();
    
        return response()->json(['message' => 'Đổi mật khẩu thành công!']);
    }
    

}
