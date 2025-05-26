<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table = 'voucher';
    public $timestamps = false;

    protected $fillable = [
        'magiamgia',
        'giagiam',
    ];
}
