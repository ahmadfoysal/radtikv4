<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class RadiusProfile extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'rate_limit',
        'validity',
        'mac_binding',
        'user_id',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }
}
