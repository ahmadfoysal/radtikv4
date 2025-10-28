<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use App\Models\User;

class Router extends Model
{

    protected $fillable = [
        'name',
        'address',
        'port',
        'ssh_port',
        'use_radius',
        'username',
        'password',
        'router_login',
        'note',
        'user_id',
        'zone_id',
    ];

    public function decryptedPassword(): string
    {
        return Crypt::decryptString($this->password);
    }

    // Add any relationships or additional methods as needed

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
