<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class VoucherTemplate extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'component',
        'preview_image',
        'is_active',
        'user_id',
    ];
}
