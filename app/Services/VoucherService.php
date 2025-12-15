<?php

namespace App\Services;

use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\MikroTik\Actions\HotspotUserManager;

class VoucherService
{
    /**
     * Get paginated vouchers with filters, scoped to user's accessible routers.
     *
     * @param User $user The authenticated user
     * @param array $filters Filter options: ['q' => string, 'status' => string, 'routerFilter' => string]
     * @param int $perPage Number of items per page
     * @return LengthAwarePaginator
     */
    public function getPaginatedVouchers(User $user, array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        // Get accessible router IDs based on user role
        $accessibleRouters = $user->getAccessibleRouters();
        $accessibleRouterIds = $accessibleRouters->pluck('id')->toArray();

        // If no accessible routers, return empty paginator
        if (empty($accessibleRouterIds)) {
            return new LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                1
            );
        }

        $query = Voucher::query()
            // Only show vouchers from accessible routers
            ->whereIn('router_id', $accessibleRouterIds)
            // Eager load relationships to prevent N+1 queries
            ->with(['router:id,name', 'profile:id,name', 'creator:id,name']);

        // Apply search filter
        if (!empty($filters['q'])) {
            $term = '%' . strtolower($filters['q']) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(username) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(batch) LIKE ?', [$term]);
            });
        }

        // Apply router filter (only if router is accessible)
        if (
            !empty($filters['routerFilter'])
            && $filters['routerFilter'] !== 'all'
            && $filters['routerFilter'] !== ''
            && $filters['routerFilter'] !== null
        ) {
            $routerId = (int) $filters['routerFilter'];
            // Only apply filter if router is accessible
            if (in_array($routerId, $accessibleRouterIds)) {
                $query->where('router_id', $routerId);
            }
        }

        // Apply status filter
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Delete a voucher if user has access to its router.
     *
     * @param User $user The authenticated user
     * @param int $voucherId The voucher ID to delete
     * @return array ['success' => bool, 'message' => string]
     * @throws ModelNotFoundException
     */
    public function deleteVoucher(User $user, int $voucherId): array
    {
        $voucher = Voucher::find($voucherId);

        if (!$voucher) {
            return [
                'success' => false,
                'message' => 'Voucher not found.',
            ];
        }

        // Verify user has access to the voucher's router
        try {
            $user->getAuthorizedRouter($voucher->router_id);
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'You are not authorized to delete this voucher.',
            ];
        }

        $voucher->delete();

        return [
            'success' => true,
            'message' => 'Voucher deleted successfully.',
        ];
    }

    /**
     * Reset a voucher/hotspot user on MikroTik router.
     * 
     * This method performs a complete reset of the hotspot user by:
     * 1. Removing session cookies for the user
     * 2. Setting MAC address to 00:00:00:00:00:00
     * 3. Removing user from active sessions
     * 4. Resetting data counters (bytes-in, bytes-out) to 0
     * 
     * The method verifies that the user has access to the voucher's router
     * before performing any operations.
     *
     * @param User $user The authenticated user performing the reset
     * @param int $voucherId The voucher ID to reset
     * @return array [
     *     'success' => bool,
     *     'message' => string,
     *     'actions' => array|null,
     *     'errors' => array|null
     * ]
     * @throws ModelNotFoundException If voucher not found or user lacks router access
     */
    public function resetVoucher(User $user, int $voucherId): array
    {
        // Step 1: Find the voucher
        $voucher = Voucher::find($voucherId);

        if (!$voucher) {
            return [
                'success' => false,
                'message' => 'Voucher not found.',
                'actions' => null,
                'errors' => ['Voucher with ID ' . $voucherId . ' does not exist'],
            ];
        }

        // Step 2: Verify user has access to the voucher's router
        try {
            $router = $user->getAuthorizedRouter($voucher->router_id);
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'You are not authorized to reset this voucher.',
                'actions' => null,
                'errors' => ['User does not have access to router ID ' . $voucher->router_id],
            ];
        }

        // Step 3: Validate voucher has username (required for MikroTik operations)
        if (empty($voucher->username)) {
            return [
                'success' => false,
                'message' => 'Voucher username is missing. Cannot reset voucher.',
                'actions' => null,
                'errors' => ['Voucher username is empty or null'],
            ];
        }

        // Step 4: Perform reset operation on MikroTik router
        try {
            $manager = app(HotspotUserManager::class);
            $result = $manager->resetVoucher($router, $voucher->username);

            // Step 5: Handle the result from HotspotUserManager
            if ($result['ok'] ?? false) {
                return [
                    'success' => true,
                    'message' => $result['message'] ?? 'Voucher reset successfully.',
                    'actions' => $result['actions'] ?? [],
                    'errors' => $result['errors'] ?? [],
                ];
            } else {
                // MikroTik operation failed
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to reset voucher on router.',
                    'actions' => $result['actions'] ?? [],
                    'errors' => $result['errors'] ?? ['Unknown error occurred during reset'],
                ];
            }
        } catch (\Throwable $e) {
            // Catch any unexpected errors during the reset process
            return [
                'success' => false,
                'message' => 'An error occurred while resetting the voucher: ' . $e->getMessage(),
                'actions' => null,
                'errors' => [
                    'Exception: ' . get_class($e),
                    'Message: ' . $e->getMessage(),
                    'File: ' . $e->getFile() . ':' . $e->getLine(),
                ],
            ];
        }
    }

    /**
     * Toggle voucher disabled status if user has access to its router.
     *
     * @param User $user The authenticated user
     * @param int $voucherId The voucher ID to toggle
     * @return array ['success' => bool, 'message' => string, 'new_status' => string|null]
     * @throws ModelNotFoundException
     */
    public function toggleVoucherStatus(User $user, int $voucherId): array
    {
        $voucher = Voucher::find($voucherId);

        if (!$voucher) {
            return [
                'success' => false,
                'message' => 'Voucher not found.',
                'new_status' => null,
            ];
        }

        // Verify user has access to the voucher's router
        try {
            $user->getAuthorizedRouter($voucher->router_id);
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'You are not authorized to modify this voucher.',
                'new_status' => null,
            ];
        }

        // Toggle status
        $voucher->status = $voucher->status === 'disabled' ? 'active' : 'disabled';
        $voucher->save();

        return [
            'success' => true,
            'message' => 'Voucher status updated successfully.',
            'new_status' => $voucher->status,
        ];
    }

    /**
     * Get voucher by ID if user has access to its router.
     *
     * @param User $user The authenticated user
     * @param int $voucherId The voucher ID
     * @return Voucher|null
     * @throws ModelNotFoundException
     */
    public function getVoucherForUser(User $user, int $voucherId): ?Voucher
    {
        $voucher = Voucher::find($voucherId);

        if (!$voucher) {
            return null;
        }

        // Verify user has access to the voucher's router
        try {
            $user->getAuthorizedRouter($voucher->router_id);
        } catch (ModelNotFoundException $e) {
            return null;
        }

        return $voucher;
    }

    /**
     * Build a query for vouchers with filters, scoped to user's accessible routers.
     * Useful for building custom queries.
     *
     * @param User $user The authenticated user
     * @param array $filters Filter options
     * @return Builder
     */
    public function buildVoucherQuery(User $user, array $filters = []): Builder
    {
        // Get accessible router IDs based on user role
        $accessibleRouters = $user->getAccessibleRouters();
        $accessibleRouterIds = $accessibleRouters->pluck('id')->toArray();

        $query = Voucher::query();

        // Only show vouchers from accessible routers
        if (!empty($accessibleRouterIds)) {
            $query->whereIn('router_id', $accessibleRouterIds);
        } else {
            // Return empty query if no accessible routers
            $query->whereRaw('1 = 0');
        }

        // Apply search filter
        if (!empty($filters['q'])) {
            $term = '%' . strtolower($filters['q']) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(username) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(batch) LIKE ?', [$term]);
            });
        }

        // Apply router filter
        if (
            !empty($filters['routerFilter'])
            && $filters['routerFilter'] !== 'all'
            && $filters['routerFilter'] !== ''
            && $filters['routerFilter'] !== null
        ) {
            $routerId = (int) $filters['routerFilter'];
            if (in_array($routerId, $accessibleRouterIds)) {
                $query->where('router_id', $routerId);
            }
        }

        // Apply status filter
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
