<?php

namespace App\Policies;

use App\Models\Router;
use App\Models\User;

class RouterPolicy
{
    /**
     * Determine if the user can view the router.
     */
    public function view(User $user, Router $router): bool
    {
        // Superadmin can view any router
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Admin can view their own routers
        if ($user->hasRole('admin') && $router->user_id === $user->id) {
            return true;
        }

        // Reseller can view routers assigned to them
        if ($user->hasRole('reseller')) {
            return $user->resellerRouters()->where('routers.id', $router->id)->exists();
        }

        return false;
    }

    /**
     * Determine if the user can create routers.
     */
    public function create(User $user): bool
    {
        // Admins and resellers can create routers
        return $user->hasRole(['admin', 'reseller']);
    }

    /**
     * Determine if the user can update the router.
     */
    public function update(User $user, Router $router): bool
    {
        // Superadmin can update any router
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Admin can update their own routers
        if ($user->hasRole('admin') && $router->user_id === $user->id) {
            return true;
        }

        // Reseller can update routers assigned to them (with permission)
        if ($user->hasRole('reseller') && $user->hasPermissionTo('edit_router')) {
            return $user->resellerRouters()->where('routers.id', $router->id)->exists();
        }

        return false;
    }

    /**
     * Determine if the user can delete the router.
     */
    public function delete(User $user, Router $router): bool
    {
        // Only superadmin and router owner (admin) can delete
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if ($user->hasRole('admin') && $router->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can access the router (general access check).
     */
    public function access(User $user, Router $router): bool
    {
        return $this->view($user, $router);
    }
}
