<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view the user list.
     */
    public function view(User $user): bool
    {
        // Only superadmin and admin can view user lists
        return $user->hasRole(['superadmin', 'admin']);
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        // Only superadmin and admin can create users
        return $user->hasRole(['superadmin', 'admin']);
    }

    /**
     * Determine if the user can update users.
     */
    public function update(User $user, User $model): bool
    {
        // Superadmin can update any user
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Admin can update their resellers
        if ($user->hasRole('admin') && $model->hasRole('reseller') && $model->admin_id === $user->id) {
            return true;
        }

        // Users can update their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine if the user can delete users.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Superadmin can delete any user except other superadmins
        if ($user->hasRole('superadmin') && !$model->hasRole('superadmin')) {
            return true;
        }

        // Admin can delete their resellers
        if ($user->hasRole('admin') && $model->hasRole('reseller') && $model->admin_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can impersonate other users.
     */
    public function impersonate(User $user, User $model): bool
    {
        // Only superadmin can impersonate
        if (!$user->hasRole('superadmin')) {
            return false;
        }

        // Cannot impersonate yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot impersonate other superadmins
        return !$model->hasRole('superadmin');
    }

    /**
     * Determine if the user can suspend other users.
     */
    public function suspend(User $user, User $model): bool
    {
        // Only superadmin can suspend
        if (!$user->hasRole('superadmin')) {
            return false;
        }

        // Cannot suspend yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot suspend other superadmins
        return !$model->hasRole('superadmin');
    }
}
