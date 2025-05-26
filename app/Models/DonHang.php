<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonHang extends Model
{
    protected $table = 'don_hang';
    public $timestamps = false;

    protected $fillable = [
        'ngay',
        'tongtien',
        'trangthai',
        'pttt',
        'hoten',
        'diachi',
        'dienthoai',
        'idvoucher',
        'idkhachhang',
        'ghichu',
    ];

    // ENUM (Laravel không hỗ trợ ENUM natively, nhưng ta có thể validate)
    const STATUS_CHOICES = [
        'chờ xử lý',
        'đang giao',
        'đã giao',
        'đã hủy',
    ];

    // Relations
    public function khachHang()
    {
        return $this->belongsTo(KhachHang::class, 'idkhachhang');
    }

    public function chiTietDonHang()
    {
        return $this->hasMany(ChiTietDonHang::class, 'donhang_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'idvoucher');
    }
}
