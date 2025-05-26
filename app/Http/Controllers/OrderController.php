<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SanPham;
use App\Models\DanhMuc;
use App\Models\KhachHang;
use App\Models\DonHang;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function getRevenueStats(Request $request)
    {
        $type = $request->query('type', 'ngay'); // mặc định là theo ngày
    
        if ($type === 'thang') {
            $data = DonHang::select(DB::raw("DATE_FORMAT(ngay, '%Y-%m') as label, SUM(tongtien) as revenue"))
                ->where('trangthai', '!=', 'đã hủy')
                ->groupBy('label')
                ->orderBy('label')
                ->get();
        } elseif ($type === 'nam') {
            $data = DonHang::select(DB::raw("YEAR(ngay) as label, SUM(tongtien) as revenue"))
                ->where('trangthai', '!=', 'đã hủy')
                ->groupBy('label')
                ->orderBy('label')
                ->get();
        } else {
            // Theo ngày
            $data = DonHang::select(DB::raw("ngay as label, SUM(tongtien) as revenue"))
                ->where('trangthai', '!=', 'đã hủy')
                ->groupBy('label')
                ->orderBy('label')
                ->get();
        }
    
        return response()->json($data);
    }
    public function stats(Request $request)
    {
        // 1. Tổng số sản phẩm
        $sanPhamCount = SanPham::count();

        // 2. Tổng số danh mục
        $danhMucCount = DanhMuc::count();

        // 3. Tổng số thành viên (khách hàng)
        $thanhVienCount = KhachHang::count();

        // 4. Tổng số lượng tồn kho (giả sử cột 'soluong' trong bảng san_pham)
        $soLuong = SanPham::sum('soluong');

        // 5. Tổng số đơn hàng
        $donHangCount = DonHang::count();

        // 6. Tổng số voucher
        $voucherCount = Voucher::count();

        return response()->json([
            'sanPham'   => $sanPhamCount,
            'danhMuc'   => $danhMucCount,
            'thanhVien' => $thanhVienCount,
            'soLuong'   => $soLuong,
            'donHang'   => $donHangCount,
            'voucher'   => $voucherCount,
        ]);
    }
    public function capNhatTrangThai(Request $request, $id)
    {
        // Kiểm tra dữ liệu đầu vào
        $validated = $request->validate([
            'trangthai' => 'required|in:chờ xử lý,đang giao,đã giao,đã hủy',
        ]);

        // Tìm đơn hàng
        $donHang = DonHang::find($id);

        if (!$donHang) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        // Cập nhật trạng thái
        $donHang->trangthai = $validated['trangthai'];
        $donHang->save();

        return response()->json([
            'message' => 'Cập nhật trạng thái thành công',
            'donhang' => $donHang
        ]);
    }
    public function show($id)
    {
        $order = DonHang::with(['chiTietDonHang.sanpham', 'voucher'])->find($id);
    
        if (!$order) {
            return response()->json(['error' => 'Không tìm thấy đơn hàng'], 404);
        }
    
        return response()->json($order);
    }
}

