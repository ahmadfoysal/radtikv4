<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Router extends Model
{
    //  $table->id();
    //         $table->string('name');
    //         $table->string('address')->nullable();
    //         $table->string('port');
    //         $table->string('ssh_port')->nullable();
    //         $table->boolean('use_radius')->default(false);
    //         $table->string('username');
    //         $table->string('password');
    //         $table->string('router_login')->nullable();
    //         $table->string('note')->nullable();
    //         $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    //         $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();


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
}
