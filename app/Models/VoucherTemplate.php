<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherTemplate extends Model
{
    protected $fillable = [
        'name',
        'component',
        'preview_image',
        'is_active',
        'user_id',
    ];
}
