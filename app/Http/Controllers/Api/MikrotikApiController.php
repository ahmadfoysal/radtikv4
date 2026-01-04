<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\Voucher;
use App\Services\VoucherLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MikrotikApiController extends Controller
{
    /**
     * MikroTik pulls new vouchers to create users.
     * GET /api/mikrotik/pull-users
     */
    public function pullInactiveUsers(Request $request)
    {
        $token = $request->query('token');

        $router = Router::where('app_key', $token)->first();

        if (! $router) {
            return response('Invalid Token', 401);
        }

        // Fetch vouchers with Profile relation
        $vouchers = $router->vouchers()
            ->with('profile')
            ->where('status', 'inactive')
            // ->limit(50) // Adjusted limit
            ->get();

        if ($request->query('format') === 'flat') {
            $lines = $vouchers->map(function ($v) {
                // 1. Get Profile Name from relation
                $pName = $v->profile->name ?? 'default';

                // 2. Get Lock Status from Profile (Adjust 'is_mac_bind' to your actual column name)
                $isLock = $v->profile->mac_binding ?? false ? '1' : '0';

                // 3. Generate Comment
                $comment = "RADTik | LOCK={$isLock}";

                // 4. Return Flat Line: User;Pass;Profile;Comment
                return implode(';', [
                    $v->username,
                    $v->password,
                    $pName,
                    $comment,
                ]);
            })->implode("\n");

            return response($lines, 200)
                ->header('Content-Type', 'text/plain');
        }

        // JSON Fallback
        return response()->json([
            'router' => $router->name,
            'count' => $vouchers->count(),
            'data' => $vouchers,
        ]);
    }

    /* Mikrotik pull active users */

    /**
     * Pull Active Users Endpoint
     * Returns list of valid users to restore on router.
     */
    public function pullActiveUsers(Request $request)
    {
        $token = $request->query('token');
        $router = Router::where('app_key', $token)->first();

        if (! $router) {
            return response('Invalid Token', 403);
        }

        // Fetch users that are NOT expired or disabled
        // We include both 'active' and 'inactive' status because users might need to login again.
        $vouchers = Voucher::with('profile')
            ->where('router_id', $router->id)
            // ->whereIn('status', ['active', 'inactive'])
            ->get();

        // Format: username;password;profile;comment
        $lines = $vouchers->map(function ($v) {

            // Determine Lock Flag from Profile
            $isLock = $v->profile->is_mac_binding ?? false ? '1' : '0';

            // Build Comment (preserve existing activation info if available)
            $baseComment = "RADTik | LOCK={$isLock}";
            if ($v->activated_at) {
                // If already activated, send the date back so script doesn't reset it logic
                // Format: "RADTik | LOCK=1 | Act: Dec/04/2025 10:00:00"
                $actDate = \Carbon\Carbon::parse($v->activated_at)->format('M/d/Y H:i:s');
                $baseComment .= " | Act: {$actDate}";
            }

            return implode(';', [
                $v->username,
                $v->password,
                $v->profile->name ?? 'default',
                $baseComment,
            ]);
        })->implode("\n");

        return response($lines, 200)
            ->header('Content-Type', 'text/plain');
    }



    public function pullProfiles(Request $request)
    {
        // changed
        $token = $request->query('token');

        $router = Router::where('app_key', $token)->first();

        if (! $router) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $profiles = $router->user->profiles()->get();

        if ($request->query('format') === 'flat') {
            $lines = $profiles->map(function ($p) {
                return implode(';', [
                    $p->name,
                    (int) $p->shared_users,
                    (string) $p->rate_limit,
                ]);
            })->implode("\n");

            return response($lines, 200)
                ->header('Content-Type', 'text/plain');
        }

        $profilesJson = $profiles->map(function ($p) {
            return [
                'name' => $p->name,
                'shared_users' => $p->shared_users,
                'rate_limit' => $p->rate_limit,
            ];
        });

        return response()->json([
            'router_id' => $router->id,
            'count' => $profilesJson->count(),
            'profiles' => $profilesJson,
        ]);
    }



    public function pushActiveUsers(Request $request)
    {
        // 1. Authenticate
        $token = $request->query('token');
        $router = Router::where('app_key', $token)->first();

        if (!$router) {
            return response()->json(['error' => 'Invalid token'], 403);
        }

        // 2. Get Data
        $content = $request->getContent();
        if (empty($content)) {
            return response()->json(['status' => 'no_data']);
        }

        //logging the received content for debugging
        Log::info("MikroTik Push Active Users Data Received: " . substr($content, 0, 500));

        $lines = explode("\n", $content);
        $userData = [];
        $usernames = [];

        // 3. Process Lines
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Limit 6 prevents comment truncation if it contains semicolons
            $parts = explode(';', $line, 6);

            if (count($parts) < 6) continue;

            [$username, $mac, $bytesIn, $bytesOut, $uptime, $comment] = $parts;

            // STRICT FILTER: Only process if "Act:" or "ACT=" is present (case-insensitive)
            // This double-checks the MikroTik script logic
            if (
                stripos($comment, 'act:') === false &&
                stripos($comment, 'act=') === false
            ) {
                continue;
            }

            $usernames[] = $username;
            $userData[$username] = compact('mac', 'bytesIn', 'bytesOut', 'uptime', 'comment');
        }

        if (empty($usernames)) {
            return response()->json(['status' => 'no_active_users_found']);
        }

        // 4. Fetch Vouchers
        $vouchers = Voucher::with('profile')
            ->where('router_id', $router->id)
            ->whereIn('username', $usernames)
            ->get();

        $updatedCount = 0;

        // 5. Update Database
        foreach ($vouchers as $voucher) {
            $username = $voucher->username;

            if (!isset($userData[$username])) continue;

            $data = $userData[$username];

            $updateData = [
                'mac_address' => !empty($data['mac']) ? $data['mac'] : $voucher->mac_address,
                // MikroTik: bytes-in = download (received), bytes-out = upload (sent)
                'bytes_out'   => (int) $data['bytesIn'], // Downloaded
                'bytes_in'    => (int) $data['bytesOut'], // Uploaded
                'up_time'     => $data['uptime'],
                'status'      => 'active',
                'updated_at'  => now(),
            ];

            // 6. Handle Activation Date (First Time Only)
            $isFirstActivation = false;
            if (is_null($voucher->activated_at)) {
                $activationTimestamp = $this->parseActivationTimestamp($data['comment'], $voucher->id);

                if ($activationTimestamp) {
                    $updateData['activated_at'] = $activationTimestamp;

                    // Calculate Expiry
                    if (is_null($voucher->expires_at)) {
                        $expiresAt = $this->calculateExpiryDate($voucher, $activationTimestamp);
                        if ($expiresAt) {
                            $updateData['expires_at'] = $expiresAt;
                        }
                    }

                    $isFirstActivation = true;
                    Log::info("Voucher Activated via Push: {$username}");
                }
            }

            $voucher->update($updateData);

            // Log activation event to voucher_logs
            if ($isFirstActivation) {
                VoucherLogger::log(
                    $voucher->fresh(),
                    $router,
                    'activated',
                    [
                        'activation_source' => 'mikrotik_push',
                        'mac_address' => $data['mac'],
                    ]
                );
            }

            $updatedCount++;
        }

        return response()->json([
            'status' => 'success',
            'processed' => $updatedCount,
        ]);
    }

    /**
     * Helper: Parse activation timestamp from comment
     */
    private function parseActivationTimestamp(string $comment, int $voucherId): ?Carbon
    {
        // Regex looks for "Act: <date>" or "ACT=<date>" until the next pipe or end of string
        if (preg_match('/Act[:=]\s*([^|]+)/i', $comment, $matches)) {
            $dateStr = trim($matches[1]);
            try {
                // Handle MikroTik default format "M/d/Y H:i:s" (e.g., dec/04/2025 10:00:00)
                if (str_contains($dateStr, '/')) {
                    return Carbon::createFromFormat('M/d/Y H:i:s', ucfirst($dateStr));
                }
                // Handle standard format
                return Carbon::parse($dateStr);
            } catch (\Exception $e) {
                Log::error("MikroTik Date Parse Error [Voucher {$voucherId}]: " . $e->getMessage());
            }
        }
        return null;
    }

    /**
     * Helper: Calculate expiry date based on profile validity
     */
    private function calculateExpiryDate(Voucher $voucher, Carbon $activation): ?Carbon
    {
        if (!$voucher->profile || !$voucher->profile->validity) {
            return null;
        }

        try {
            $validity = strtolower(trim($voucher->profile->validity));
            $expiresAt = $activation->copy();
            $amount = (int) $validity;

            if ($amount <= 0) return null;

            if (str_contains($validity, 'h')) {
                return $expiresAt->addHours($amount);
            }
            if (str_contains($validity, 'm') && !str_contains($validity, 'mo')) {
                return $expiresAt->addMinutes($amount);
            }

            // Default to days
            return $expiresAt->addDays($amount);
        } catch (\Exception $e) {
            return null;
        }
    }
    /**
     * Smart Cleanup Endpoint
     * Receives comma-separated usernames from router.
     * Returns newline-separated usernames that should be deleted.
     */
    public function syncOrphans(Request $request)
    {
        // 1. Authenticate Router
        $token = $request->query('token');
        $router = Router::where('app_key', $token)->first();

        if (! $router) {
            return response('Invalid Token', 403);
        }

        // 2. Get Router's User List from Body
        $content = $request->getContent();

        if (empty($content)) {
            // No users sent means nothing to check
            return response('', 200);
        }

        // Convert comma-separated string to array
        $routerUsers = explode(',', $content);

        // 3. Find which of these users exist in Database
        // We only check against the users sent by the router to be efficient
        $validUsers = Voucher::where('router_id', $router->id)
            ->whereIn('username', $routerUsers)
            ->pluck('username')
            ->toArray();

        // 4. Calculate Orphans
        // Orphans = (Router List) - (Database List)
        $orphans = array_diff($routerUsers, $validUsers);

        // 5. Return Orphans as Flat List (Line by line)
        // If array is empty, it returns empty string (which stops the router script correctly)
        return response(implode("\n", $orphans), 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Pull Updated Profiles Endpoint
     * Returns profiles that have been updated.
     */
    public function pullUpdatedProfiles(Request $request)
    {
        $token = $request->query('token');
        $router = Router::where('app_key', $token)->first();

        if (! $router) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // Get profiles updated after a specific timestamp if provided
        $since = $request->query('since');
        $query = $router->user->profiles();

        if ($since) {
            try {
                $sinceDate = Carbon::parse($since);
                $query->where('updated_at', '>', $sinceDate);
            } catch (\Exception $e) {
                // If date parsing fails, return all profiles
            }
        }

        $profiles = $query->get();

        if ($request->query('format') === 'flat') {
            $lines = $profiles->map(function ($p) {
                return implode(';', [
                    $p->name,
                    (int) $p->shared_users,
                    (string) $p->rate_limit,
                ]);
            })->implode("\n");

            return response($lines, 200)
                ->header('Content-Type', 'text/plain');
        }

        $profilesJson = $profiles->map(function ($p) {
            return [
                'name' => $p->name,
                'shared_users' => $p->shared_users,
                'rate_limit' => $p->rate_limit,
                'updated_at' => $p->updated_at,
            ];
        });

        return response()->json([
            'router_id' => $router->id,
            'count' => $profilesJson->count(),
            'profiles' => $profilesJson,
        ]);
    }
}
