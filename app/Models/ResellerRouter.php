<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerRouter extends Model
{
    protected $table = 'reseller_router';

    protected $fillable = [
        'router_id',
        'reseller_id',
        'assigned_by',
    ];

    /**
     * Get the router that belongs to this reseller assignment.
     */
    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Get the reseller user assigned to this router.
     */
    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    /**
     * Get the user who assigned this router to the reseller.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
