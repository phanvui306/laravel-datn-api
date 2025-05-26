<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\DonHang;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function getVouchers()
    {
        try {
            $vouchers = Voucher::all();

            if ($vouchers->isEmpty()) {
                return response()->json(['message' => 'Không có voucher nào'], 404);
            }

            return response()->json($vouchers);
        } catch (\Exception $error) {
            return response()->json(['message' => 'Lỗi server', 'error' => $error->getMessage()], 500);
        }
    }
    public function validateVoucher(Request $request)
    {
        $voucherCode = $request->voucherCode;

        if (empty($voucherCode)) {
            return response()->json(['error' => 'Vui lòng nhập mã giảm giá hợp lệ.'], 400);
        }

        try {
            $voucher = Voucher::where('magiamgia', trim($voucherCode))->first();

            if (!$voucher) {
                return response()->json(['error' => 'Mã giảm giá không hợp lệ'], 400);
            }

            return response()->json([
                'discount' => $voucher->giagiam,
            ], 200);

        } catch (\Exception $err) {
            return response()->json(['error' => 'Đã xảy ra lỗi khi kiểm tra mã giảm giá'], 500);
        }
    }
    public function store(Request $request) 
    {
        // Kiểm tra dữ liệu đầu vào
        $validated = $request->validate([
            'magiamgia' => 'required|string|unique:voucher,magiamgia|max:255',
            'giagiam' => 'required|numeric|min:0',
        ]);

        // Tạo mới voucher
        $voucher = Voucher::create([
            'magiamgia' => $validated['magiamgia'],
            'giagiam' => $validated['giagiam'],
        ]);

        return response()->json([
            'message' => 'Voucher đã được tạo thành công.',
            'voucher' => $voucher,
        ], 201);
    }
    public function destroy($id)
    {
        // Tìm voucher
        $voucher = Voucher::find($id);
    
        if (!$voucher) {
            return response()->json(['message' => 'Voucher không tồn tại'], 404);
        }
    
        // Kiểm tra xem voucher có đang được sử dụng trong đơn hàng không
        $isUsedInOrder = DonHang::where('idvoucher', $id)->exists();
    
        if ($isUsedInOrder) {
            return response()->json(['message' => 'Voucher đang được sử dụng, không thể xóa.'], 400);
        }
    
        // Xóa voucher nếu không có đơn hàng nào tham chiếu
        $voucher->delete();
    
        return response()->json([
            'message' => 'Voucher đã được xóa thành công.',
        ] );
    }
    public function show($id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['error' => 'Không tìm thấy voucher'], 404);
        }

        return response()->json(['voucher' => $voucher], 200);
    }

    // Cập nhật voucher theo ID
    public function update(Request $request, $id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['error' => 'Không tìm thấy voucher'], 404);
        }

        $validated = $request->validate([
            'magiamgia' => 'required|string|max:50',
            'giagiam' => 'required|numeric|min:0',
        ]);

        $voucher->update($validated);

        return response()->json(['message' => 'Cập nhật voucher thành công', 'voucher' => $voucher], 200);
    }
}
