<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanhMuc extends Model
{
    protected $table = 'danh_muc';
    public $timestamps = false;

    protected $fillable = [
        'tendanhmuc',
        'ghichu',
    ];
}
