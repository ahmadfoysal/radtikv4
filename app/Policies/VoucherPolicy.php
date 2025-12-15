<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Voucher;

class VoucherPolicy
{
    /**
     * Determine if the user can view the voucher.
     */
    public function view(User $user, Voucher $voucher): bool
    {
        // Superadmin can view any voucher
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Check if user has access to the voucher's router
        try {
            $user->getAuthorizedRouter($voucher->router_id);
            return true;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return false;
        }
    }

    /**
     * Determine if the user can create vouchers.
     */
    public function create(User $user): bool
    {
        // Admins and resellers with permission can create vouchers
        return $user->hasRole(['admin']) || 
               ($user->hasRole('reseller') && $user->hasPermissionTo('generate_vouchers'));
    }

    /**
     * Determine if the user can update the voucher.
     */
    public function update(User $user, Voucher $voucher): bool
    {
        // Check if user has access to the voucher's router
        try {
            $user->getAuthorizedRouter($voucher->router_id);
            return $user->hasPermissionTo('edit_vouchers');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return false;
        }
    }

    /**
     * Determine if the user can delete the voucher.
     */
    public function delete(User $user, Voucher $voucher): bool
    {
        // Check if user has access to the voucher's router
        try {
            $user->getAuthorizedRouter($voucher->router_id);
            return $user->hasPermissionTo('delete_vouchers');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return false;
        }
    }
}
