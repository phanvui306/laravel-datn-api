<?php

namespace App\Http\Controllers;

use App\Models\DonHang;
use App\Models\SanPham;
use App\Models\ChiTietDonHang;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
 public function checkout(Request $request)
    {
        $cart = session('cart', []);

        if (count($cart) === 0) {
            return response()->json(['error' => 'Giỏ hàng trống, vui lòng thêm sản phẩm để thanh toán.'], 400);
        }

        $idkhachhang = session('user_id');
        $diachi = $request->diachi;

        if (!$idkhachhang || !$diachi) {
            return response()->json(['error' => 'Thiếu thông tin khách hàng hoặc địa chỉ giao hàng'], 400);
        }

        DB::beginTransaction();

        try {
            // Tính tổng tiền của giỏ hàng
            $tongtien = 0;
            foreach ($cart as $item) {
                $tongtien += $item['product']['gia'] * $item['quantity'];
            }

            $idvoucher = null;

            // Kiểm tra và áp dụng voucher nếu có
            if ($request->voucherCode) {
                $voucher = Voucher::where('magiamgia', $request->voucherCode)->first();
                if (!$voucher) {
                    return response()->json(['error' => 'Mã giảm giá không hợp lệ'], 400);
                }
                $tongtien -= $voucher->giagiam;
                $tongtien = max($tongtien, 0);
                $idvoucher = $voucher->id;
            }

            // Tạo đơn hàng mới
            $donhang = DonHang::create([
                'ngay' => now(),
                'tongtien' => $tongtien,
                'trangthai' => 'chờ xử lý',
                'pttt' => (int) $request->pttt,
                'hoten' => $request->hoten,
                'diachi' => $request->diachi,
                'dienthoai' => $request->dienthoai,
                'idvoucher' => $idvoucher,
                'idkhachhang' => $idkhachhang,
                'ghichu' => $request->ghichu ?: 'không có',
            ]);

            // Kiểm tra tồn kho và tạo chi tiết đơn hàng
            foreach ($cart as $item) {
                $sanpham = SanPham::findOrFail($item['product']['id']);
                if ($sanpham->soluong < $item['quantity']) {
                    throw new \Exception("Sản phẩm {$sanpham->tensanpham} không đủ hàng");
                }
                $sanpham->update(['soluong' => $sanpham->soluong - $item['quantity']]);

                ChiTietDonHang::create([
                    'donhang_id' => $donhang->id,
                    'sanpham_id' => $sanpham->id,
                    'soluong' => $item['quantity'],
                    'gia' => $sanpham->gia,
                ]);
            }

            // Nếu phương thức thanh toán là VNPAY (giả định: 0 = VNPAY)
            if ((int)$request->pttt === 0) {
                $vnp_TmnCode = "OZZIQBQS";
                $vnp_HashSecret = "10JK6VNZWA4VG944IRWRDOQWAO0M51T2";
                $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
                $vnp_Returnurl = url('/api/vnpay/return');

                $vnp_TxnRef = $donhang->id;
                $vnp_OrderInfo = 'Thanh toán đơn hàng #' . $donhang->id;
                $vnp_OrderType = 'billpayment';
                $vnp_Amount = $donhang->tongtien * 100;
                $vnp_Locale = 'vn';
                $vnp_BankCode = $request->bank_code ?? '';
                $vnp_IpAddr = request()->ip();

                $inputData = [
                    "vnp_Version" => "2.1.0",
                    "vnp_TmnCode" => $vnp_TmnCode,
                    "vnp_Amount" => $vnp_Amount,
                    "vnp_Command" => "pay",
                    "vnp_CreateDate" => now()->format('YmdHis'),
                    "vnp_CurrCode" => "VND",
                    "vnp_IpAddr" => $vnp_IpAddr,
                    "vnp_Locale" => $vnp_Locale,
                    "vnp_OrderInfo" => $vnp_OrderInfo,
                    "vnp_OrderType" => $vnp_OrderType,
                    "vnp_ReturnUrl" => $vnp_Returnurl,
                    "vnp_TxnRef" => $vnp_TxnRef,
                ];

                if (!empty($vnp_BankCode)) {
                    $inputData['vnp_BankCode'] = $vnp_BankCode;
                }

                ksort($inputData);
                $hashdata = '';
                $query = '';
                $i = 0;
                foreach ($inputData as $key => $value) {
                    $hashdata .= ($i++ ? '&' : '') . urlencode($key) . "=" . urlencode($value);
                    $query .= urlencode($key) . "=" . urlencode($value) . '&';
                }

                $vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
                $vnp_Url .= '?' . $query . 'vnp_SecureHash=' . $vnp_SecureHash;

                DB::commit();

                // Không xóa giỏ hàng ở đây vì khách có thể hủy thanh toán VNPAY
                return response()->json(['redirect' => $vnp_Url]);
            }

            // Nếu phương thức thanh toán là tiền mặt hoặc khác
            DB::commit();

            // Xóa giỏ hàng trong session sau khi đặt hàng thành công
            session(['cart' => []]);

            return response()->json(['message' => 'Đặt hàng thành công', 'donhang_id' => $donhang->id]);

        } catch (\Exception $err) {
            DB::rollBack();
            return response()->json(['error' => $err->getMessage() ?: 'Lỗi server khi đặt hàng'], 500);
        }
    }


public function checkoutNow(Request $request)
{
    $idkhachhang = session('user_id');
    $productId = $request->productId;
    $quantity = $request->quantity;
    $diachi = $request->diachi;

    if (!$idkhachhang || !$productId || !$quantity || !$diachi) {
        return response()->json(['error' => 'Thiếu thông tin cần thiết'], 400);
    }

    try {
        DB::beginTransaction();

        // Khóa dòng sản phẩm để tránh race-condition
        $sanpham = SanPham::where('id', $productId)->lockForUpdate()->firstOrFail();

        if ($sanpham->soluong < $quantity) {
            throw new \Exception('Sản phẩm không đủ số lượng');
        }

        $tongtien = $sanpham->gia * $quantity;
        $idvoucher = null;

        if ($request->voucherCode) {
            $voucher = Voucher::where('magiamgia', $request->voucherCode)->first();
            if ($voucher) {
                $tongtien = max($tongtien - $voucher->giagiam, 0);
                $idvoucher = $voucher->id;
            } else {
                return response()->json(['error' => 'Mã giảm giá không hợp lệ'], 400);
            }
        }

        // Nếu là thanh toán VNPay (pttt = 0), tạo link thanh toán tạm thời
        if ((int)$request->pttt === 0) {
            DB::commit(); // Commit sớm vì không tạo đơn hàng ngay

            $tempOrderId = uniqid('temp_');
            session(["temp_order_$tempOrderId" => [
                'idkhachhang' => $idkhachhang,
                'productId' => $productId,
                'quantity' => $quantity,
                'diachi' => $diachi,
                'hoten' => $request->hoten,
                'dienthoai' => $request->dienthoai,
                'voucherCode' => $request->voucherCode,
                'ghichu' => $request->ghichu ?: 'không có',
                'pttt' => $request->pttt,
                'tongtien' => $tongtien,
            ]]);

            $vnp_TmnCode = "OZZIQBQS";
            $vnp_HashSecret = "10JK6VNZWA4VG944IRWRDOQWAO0M51T2";
            $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
            $vnp_Returnurl = url('/api/vnpay/return');

            $vnp_TxnRef = $tempOrderId;
            $vnp_OrderInfo = 'Thanh toán đơn hàng tạm #' . $tempOrderId;
            $vnp_Amount = $tongtien * 100;

            $inputData = [
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => now()->format('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $request->ip(),
                "vnp_Locale" => "vn",
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => "billpayment",
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef,
            ];

            if ($request->bank_code) {
                $inputData['vnp_BankCode'] = $request->bank_code;
            }

            ksort($inputData);
            $hashdata = '';
            $query = '';
            $i = 0;
            foreach ($inputData as $key => $value) {
                $hashdata .= ($i++ ? '&' : '') . urlencode($key) . "=" . urlencode($value);
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            $vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= '?' . $query . 'vnp_SecureHash=' . $vnp_SecureHash;

            return response()->json(['redirect' => $vnp_Url]);
        }

        // Với thanh toán tiền mặt (pttt = 1) → tạo đơn hàng ngay
        $donhang = DonHang::create([
            'ngay' => now(),
            'tongtien' => $tongtien,
            'trangthai' => 'chờ xử lý',
            'pttt' => $request->pttt,
            'hoten' => $request->hoten,
            'diachi' => $request->diachi,
            'dienthoai' => $request->dienthoai,
            'idvoucher' => $idvoucher,
            'idkhachhang' => $idkhachhang,
            'ghichu' => $request->ghichu ?: 'không có',
            'ip_address' => $request->ip(), // Ghi lại IP nếu muốn log
        ]);

        ChiTietDonHang::create([
            'donhang_id' => $donhang->id,
            'sanpham_id' => $productId,
            'soluong' => $quantity,
            'gia' => $sanpham->gia,
        ]);

        // Trừ hàng tồn kho
        $sanpham->update(['soluong' => $sanpham->soluong - $quantity]);

        // ✅ Xoá giỏ hàng nếu có
        if (session()->has("cart.$idkhachhang")) {
            session()->forget("cart.$idkhachhang");
        }

        DB::commit();
        return response()->json(['message' => 'Đặt hàng thành công', 'donhang_id' => $donhang->id]);

    } catch (\Exception $err) {
        DB::rollBack();
        return response()->json(['error' => $err->getMessage()], 500);
    }
}



    public function getOrders()
    {
        $userId = session('user_id');

        if (!$userId) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        try {
            $orders = DonHang::where('idkhachhang', $userId)->orderByDesc('ngay')->get();
            return response()->json(['orders' => $orders]);

        } catch (\Exception $error) {
            return response()->json(['error' => 'Lỗi server'], 500);
        }
    }
public function vnpayReturn(Request $request)
{
    $inputData = $request->all();

    if ($inputData['vnp_ResponseCode'] == '00') {
        $txnRef = $inputData['vnp_TxnRef'];

        // Trường hợp thanh toán cho đơn hàng "Mua ngay" (tạm)
        if (str_starts_with($txnRef, 'temp_')) {
            $orderData = session("temp_order_$txnRef");

            if (!$orderData) {
                return redirect('/thanh-toan-that-bai')->with('error', 'Không tìm thấy đơn hàng tạm');
            }

            DB::beginTransaction();
            try {
                $sanpham = SanPham::findOrFail($orderData['productId']);

                if ($sanpham->soluong < $orderData['quantity']) {
                    throw new \Exception('Sản phẩm không đủ số lượng');
                }

                $idvoucher = null;
                if (!empty($orderData['voucherCode'])) {
                    $voucher = Voucher::where('magiamgia', $orderData['voucherCode'])->first();
                    if ($voucher) {
                        $idvoucher = $voucher->id;
                    }
                }

                $donhang = DonHang::create([
                    'ngay' => now(),
                    'tongtien' => $orderData['tongtien'],
                    'trangthai' => 'chờ xử lý',
                    'pttt' => $orderData['pttt'],
                    'hoten' => $orderData['hoten'],
                    'diachi' => $orderData['diachi'],
                    'dienthoai' => $orderData['dienthoai'],
                    'idvoucher' => $idvoucher,
                    'idkhachhang' => $orderData['idkhachhang'],
                    'ghichu' => $orderData['ghichu'],
                ]);

                $sanpham->update(['soluong' => $sanpham->soluong - $orderData['quantity']]);

                ChiTietDonHang::create([
                    'donhang_id' => $donhang->id,
                    'sanpham_id' => $orderData['productId'],
                    'soluong' => $orderData['quantity'],
                    'gia' => $sanpham->gia,
                ]);

                DB::commit();

                session()->forget("temp_order_$txnRef");

                return redirect('http://localhost:3000/thanh-toan-thanh-cong');
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect('/thanh-toan-that-bai')->with('error', $e->getMessage());
            }
        }

        // Trường hợp thanh toán cho giỏ hàng (checkout thường)
        // Trường hợp thanh toán cho giỏ hàng (checkout thường)
else {
    DB::beginTransaction();
    try {
        $donhang = DonHang::findOrFail($txnRef);

        if ($donhang->trangthai !== 'chờ xử lý') {
            throw new \Exception('Đơn hàng đã được xử lý hoặc chờ xử lý');
        }

        // ✅ Cập nhật trạng thái
        $donhang->update(['trangthai' => 'chờ xử lý']);

        // ✅ Xoá giỏ hàng khỏi session sau khi thanh toán thành công
        session(['cart' => []]);

        DB::commit();

        return redirect('http://localhost:3000/thanh-toan-thanh-cong');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect('/thanh-toan-that-bai')->with('error', $e->getMessage());
    }
}

    } else {
        return redirect('/thanh-toan-that-bai');
    }
}



}
