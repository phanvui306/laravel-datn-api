<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KhachHang extends Model
{
    use HasFactory;

    // Đặt tên bảng nếu khác với convention
    protected $table = 'khach_hang';

    // Các trường có thể gán hàng loạt
    protected $fillable = [
        'hoten', 'email', 'password', 'dienthoai', 'diachi', 'role'
    ];

    // Bỏ qua các trường `created_at` và `updated_at` (nếu không dùng timestamps)
    public $timestamps = false;
}
