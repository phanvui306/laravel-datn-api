<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DonHang;
use App\Models\SanPham;
use App\Models\Voucher;
use Carbon\Carbon;

class VNPayController extends Controller
{
    // API trả về thông tin thanh toán VNPAY
    public function createPayment(Request $request)
    {
        $idkhachhang = session('user_id');
        $productId = $request->productId;
        $quantity = $request->quantity;
        $diachi = $request->diachi;

        if (!$idkhachhang || !$productId || !$quantity || !$diachi) {
            return response()->json(['error' => 'Thiếu thông tin cần thiết'], 400);
        }

        DB::beginTransaction();

        try {
            $sanpham = SanPham::findOrFail($productId);

            if ($sanpham->soluong < $quantity) {
                throw new \Exception('Sản phẩm không đủ số lượng');
            }

            $tongtien = $sanpham->gia * $quantity;
            $idvoucher = null;

            if ($request->voucherCode) {
                $voucher = Voucher::where('magiamgia', $request->voucherCode)->first();
                if ($voucher) {
                    $tongtien -= $voucher->giagiam;
                    if ($tongtien < 0) $tongtien = 0;
                    $idvoucher = $voucher->id;
                }
            }

            // Tạo đơn hàng
            $donhang = DonHang::create([
                'ngay' => now(),
                'tongtien' => $tongtien,
                'trangthai' => 'chờ xử lý',
                'pttt' => 'VNPAY',
                'hoten' => $request->hoten,
                'diachi' => $request->diachi,
                'dienthoai' => $request->dienthoai,
                'idvoucher' => $idvoucher,
                'idkhachhang' => $idkhachhang,
                'ghichu' => $request->ghichu ?: 'không có',
            ]);

            // Cập nhật số lượng sản phẩm
            $sanpham->update(['soluong' => $sanpham->soluong - $quantity]);

            // Tạo chi tiết đơn hàng
            ChiTietDonHang::create([
                'donhang_id' => $donhang->id,
                'sanpham_id' => $productId,
                'soluong' => $quantity,
                'dongia' => $sanpham->gia,
            ]);

            DB::commit();

            // Thực hiện tạo yêu cầu thanh toán đến VNPAY
            $vnp_TmnCode = 'OZZIQBQS'; // Tên mã thương mại của bạn
            $vnp_HashSecret = '10JK6VNZWA4VG944IRWRDOQWAO0M51T2'; // Mã bí mật của bạn
            $vnp_Url = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'; // URL thanh toán VNPAY

            $vnp_TxnRef = $donhang->id; // Mã đơn hàng
            $vnp_OrderInfo = "Thanh toán đơn hàng ID: {$donhang->id}";
            $vnp_OrderType = 'billpayment'; // Loại giao dịch
            $vnp_Amount = $tongtien * 100; // Số tiền cần thanh toán (VNPAY yêu cầu tính bằng đồng)

            // Thời gian giao dịch
            $vnp_Locale = 'vn';
            $vnp_IpAddr = $request->ip();
            $vnp_CreateDate = Carbon::now('Asia/Ho_Chi_Minh')->format('YmdHis');
            $vnp_ExpireDate = Carbon::now('Asia/Ho_Chi_Minh')->addMinutes(15)->format('YmdHis');

            // Chuẩn bị tham số cho URL thanh toán
            $inputData = [
                'vnp_Version' => '2.1.0',
                'vnp_TmnCode' => $vnp_TmnCode,
                'vnp_TxnRef' => $vnp_TxnRef,
                'vnp_OrderInfo' => $vnp_OrderInfo,
                'vnp_OrderType' => $vnp_OrderType,
                'vnp_Amount' => $vnp_Amount,
                'vnp_Locale' => $vnp_Locale,
                'vnp_IpAddr' => $vnp_IpAddr,
                'vnp_CreateDate' => $vnp_CreateDate,
                'vnp_ExpireDate' => $vnp_ExpireDate,
            ];

            // Mã hóa tham số và tính toán chữ ký
            ksort($inputData);
            $query = http_build_query($inputData);
            $vnp_SecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
            $inputData['vnp_SecureHash'] = $vnp_SecureHash;

            // Tạo URL thanh toán
            $vnp_Url = $vnp_Url . '?' . http_build_query($inputData);

            return response()->json(['url' => $vnp_Url]);

        } catch (\Exception $err) {
            DB::rollBack();
            return response()->json(['error' => $err->getMessage() ?: 'Lỗi server khi tạo đơn hàng'], 500);
        }
    }
    // API xử lý phản hồi từ VNPAY
    public function vnpayReturn(Request $request)
    {
        $vnp_TmnCode = 'OZZIQBQS'; // Tên mã thương mại của bạn
        $vnp_HashSecret = '10JK6VNZWA4VG944IRWRDOQWAO0M51T2'; // Mã bí mật của bạn

        $vnp_ResponseCode = $request->vnp_ResponseCode; // Mã phản hồi từ VNPAY
        $vnp_TxnRef = $request->vnp_TxnRef; // Mã đơn hàng
        $vnp_Amount = $request->vnp_Amount / 100; // Số tiền thanh toán
        $vnp_SecureHash = $request->vnp_SecureHash; // Chữ ký bảo mật trả về

        // Kiểm tra chữ ký bảo mật
        $inputData = $request->except('vnp_SecureHash');
        ksort($inputData);
        $query = http_build_query($inputData);
        $secureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
        if ($secureHash !== $vnp_SecureHash) {
            return redirect('http://localhost:3000/thanhtoan-thatbai?message=chu-ky-khong-hop-le'); // Tùy đường dẫn frontend của bạn
        }

        if ($vnp_ResponseCode == '00') {
            // Thành công
            $donhang = DonHang::find($vnp_TxnRef);
            if ($donhang) {
                $donhang->trangthai = 'Đã thanh toán';
                $donhang->save();
            }

            return redirect('http://localhost:3000/thanh-toan-thanh-cong'); // <== đường dẫn frontend bạn muốn hiển thị
        } else {
            return redirect('http://localhost:3000/thanh-toan-mua-ngay?message=loi-thanh-toan');
        }
    }
}
