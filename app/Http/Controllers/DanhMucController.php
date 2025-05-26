<?php

namespace App\Http\Controllers;

use App\Models\DanhMuc;
use Illuminate\Http\Request;

class DanhMucController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tendanhmuc' => 'required|string|max:255',
            'ghichu' => 'nullable|string',
        ]);

        $danhMuc = DanhMuc::create($validated);
        return response()->json(['message' => 'Thêm danh mục thành công', 'data' => $danhMuc]);
    }
    public function show($id)
    {
        $danhMuc = DanhMuc::find($id);
        if (!$danhMuc) {
            return response()->json(['message' => 'Danh mục không tồn tại'], 404);
        }
        return $danhMuc;
    }
    public function update(Request $request, $id)
    {
        $danhMuc = DanhMuc::find($id);
        if (!$danhMuc) {
            return response()->json(['message' => 'Danh mục không tồn tại'], 404);
        }

        $validated = $request->validate([
            'tendanhmuc' => 'required|string|max:255',
            'ghichu' => 'nullable|string',
        ]);

        $danhMuc->update($validated);
        return response()->json(['message' => 'Cập nhật danh mục thành công', 'data' => $danhMuc]);
    }
    public function destroy($id)
    {
        $danhMuc = DanhMuc::find($id);
        if (!$danhMuc) {
            return response()->json(['message' => 'Danh mục không tồn tại'], 404);
        }

        $danhMuc->delete();
        return response()->json(['message' => 'Xoá danh mục thành công']);
    }
}
