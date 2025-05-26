<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SanPham;

class SanPhamController extends Controller
{
    public function store(Request $request)
    {
        $sanpham = new SanPham();
        $sanpham->tensanpham = $request->input('tensanpham');
        $sanpham->mota       = $request->input('mota', '');
        $sanpham->iddanhmuc  = $request->input('iddanhmuc');
        $sanpham->gia        = $request->input('gia');
        $sanpham->sphot      = $request->input('sphot', false);
        $sanpham->soluong    = $request->input('soluong');
        
        // Xử lý hình ảnh (hinh, hinh1, hinh2, hinh3)
        foreach (['hinh', 'hinh1', 'hinh2', 'hinh3'] as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                // Sử dụng phương thức store để lưu file vào thư mục storage/app/public
                $filename = $file->store('public/images'); // Lưu vào storage/app/public/images
                
                // Lưu đường dẫn tương đối, ví dụ: 'storage/images/filename.jpg'
                $sanpham->$field = 'storage/images/' . basename($filename); // Lưu vào DB với đường dẫn tương đối
            }
        }
    
        $sanpham->save();
    
        return response()->json([
            'message' => 'Thêm sản phẩm thành công!',
            'sanpham' => $sanpham
        ], 201);
    }
    public function show($id)
    {
        $product = SanPham::find($id);

        if (!$product) {
            return response()->json(['message' => 'Sản phẩm không tồn tại'], 404);
        }

        return response()->json($product);
    }
    // API để cập nhật sản phẩm
    public function update(Request $request, $id)
    {
        // Tìm sản phẩm theo id
        $sanpham = SanPham::findOrFail($id);

        // Kiểm tra và lấy dữ liệu đầu vào từ request
        $tensanpham = $request->input('tensanpham');
        $mota = $request->input('mota');
        $gia = $request->input('gia');
        $soluong = $request->input('soluong');
        $iddanhmuc  = $request->input('iddanhmuc');
        // Kiểm tra nếu dữ liệu cần thiết bị thiếu
        if (!$tensanpham || !$gia || !$soluong) {
            return response()->json([
                'message' => 'Các trường "Tên sản phẩm", "Giá", và "Số lượng" là bắt buộc.',
            ], 400);
        }

        // Cập nhật thông tin sản phẩm
        $sanpham->tensanpham = $tensanpham;
        $sanpham->mota = $mota;
        $sanpham->gia = $gia;
        $sanpham->soluong = $soluong;
        $sanpham->iddanhmuc = $iddanhmuc;

        // Kiểm tra và cập nhật hình ảnh nếu có
        foreach (['hinh', 'hinh1', 'hinh2', 'hinh3'] as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                // Lưu file vào thư mục public/images
                $filename = $file->store('public/images');
                
                // Lưu đường dẫn tương đối vào DB
                $sanpham->$field = 'storage/images/' . basename($filename);
            }
        }

        // Lưu sản phẩm
        $sanpham->save();

        return response()->json([
            'message' => 'Sản phẩm đã được cập nhật thành công.',
            'data' => $sanpham
        ], 200);
    }
    public function destroy($id)
    {
        // Tìm sản phẩm theo ID
        $sanpham = SanPham::find($id);

        if (!$sanpham) {
            // Nếu không tìm thấy sản phẩm, trả về lỗi 404
            return response()->json([
                'message' => 'Sản phẩm không tồn tại!'
            ], 404);
        }

        // Xóa sản phẩm
        $sanpham->delete();

        // Trả về thông báo thành công
        return response()->json([
            'message' => 'Sản phẩm đã được xóa thành công!'
        ], 200);
    }
}
