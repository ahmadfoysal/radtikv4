<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadiusServer extends Model
{
    protected $fillable = [
        'name',
        'host',
        'ssh_port',
        'username',
        'password',
        'is_active',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
