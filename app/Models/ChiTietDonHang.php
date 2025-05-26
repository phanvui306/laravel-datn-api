<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietDonHang extends Model
{
    protected $table = 'chi_tiet_don_hang';
    public $timestamps = false;

    protected $fillable = [
        'donhang_id',
        'sanpham_id',
        'soluong',
        'gia',
    ];

    public function donhang()
    {
        return $this->belongsTo(DonHang::class, 'donhang_id');
    }

    public function sanpham()
    {
        return $this->belongsTo(SanPham::class, 'sanpham_id');
    }
}
