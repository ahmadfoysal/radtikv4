<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'name',
        'rate_limit',
        'validity',
        'mac_binding',
        'price',
        'user_id',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'mac_binding' => 'boolean',
        'price' => 'decimal:2',
    ];
}
