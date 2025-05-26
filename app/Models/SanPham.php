<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SanPham extends Model
{
    protected $table = 'san_pham'; // giữ tên bảng như trong Sequelize
    public $timestamps = false;

    protected $fillable = [
        'tensanpham',
        'mota',
        'hinh',
        'hinh1',
        'hinh2',
        'hinh3',
        'iddanhmuc',
        'gia',
        'sphot',
        'soluong',
        'trangthai'
    ];

    protected static function boot()
    {
        parent::boot();

        // Hook giống beforeSave trong Sequelize
        static::saving(function ($sanpham) {
            if ($sanpham->isDirty('soluong')) {
                if ($sanpham->soluong === 0) {
                    $sanpham->trangthai = 'hết hàng';
                } else {
                    $sanpham->trangthai = 'còn hàng';
                }
            }
        });
    }
}
