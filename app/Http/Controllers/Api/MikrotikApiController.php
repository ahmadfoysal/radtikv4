<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

        if (!$router) {
            return response('Invalid Token', 401);
        }

        // Fetch vouchers with Profile relation
        $vouchers = $router->vouchers()
            ->with('profile')
            ->where('is_radius', false)
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
                    $comment
                ]);
            })->implode("\n");

            return response($lines, 200)
                ->header('Content-Type', 'text/plain');
        }

        // JSON Fallback
        return response()->json([
            'router' => $router->name,
            'count'  => $vouchers->count(),
            'data'   => $vouchers,
        ]);
    }

    /* Mikrotik pull active users */

    public function pullActiveUsers(Request $request)
    {
        $token = $request->query('token');

        $router = Router::where('app_key', $token)->first();
        if (!$router) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $vouchers = $router->vouchers()
            ->where('is_radius', false)
            ->whereNot('status', 'inactive')
            ->get()
            ->map(function ($v) {
                return [
                    'username'    => $v->username,
                    'password'    => $v->password,
                    'profile'     => $v->router_profile,
                    'validity'    => $v->expires_at ? $v->expires_at->diffInMinutes($v->activated_at) : null,
                    'comments'    => 'ACT' . ($v->activated_at ? '-ActivatedAt=' . $v->activated_at->format('Y-m-d_H:i:s') : ''),
                ];
            });

        return response()->json([
            'router_id' => $router->id,
            'count'     => $vouchers->count(),
            'vouchers'  => $vouchers,
        ]);
    }


    /**
     * MikroTik sends usage, login, mac, uptime etc to Laravel.
     * POST /api/mikrotik/push-usage
     */
    public function pushActiveUsers(Request $request)
    {
        // 1. Authenticate via URL Query Token
        $token = $request->query('token');

        $router = Router::where('app_key', $token)->first();

        if (!$router) {
            // Return 403 to prevent MikroTik "www-authenticate" header error
            return response()->json(['error' => 'Invalid token'], 403);
        }

        $content = $request->getContent();
        if (empty($content)) {
            return response()->json(['status' => 'no_data']);
        }

        $lines = explode("\n", $content);
        $userData = [];
        $usernames = [];

        // 3. Pre-process all lines from the request
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = explode(';', $line);
            if (count($parts) < 6) continue;

            [$username, $mac, $bytesIn, $bytesOut, $uptime, $comment] = $parts;
            $usernames[] = $username;
            $userData[$username] = compact('mac', 'bytesIn', 'bytesOut', 'uptime', 'comment');
        }

        if (empty($usernames)) {
            return response()->json(['status' => 'no_valid_data_found']);
        }

        // 4. Fetch all relevant vouchers in a single query
        $vouchers = Voucher::with('profile')
            ->where('router_id', $router->id)
            ->whereIn('username', $usernames)
            ->get()
            ->keyBy('username');

        $updatedCount = 0;

        // 5. Iterate over fetched vouchers and update them
        foreach ($vouchers as $username => $voucher) {
            $data = $userData[$username];
            $updateData = [
                'mac_address' => !empty($data['mac']) ? $data['mac'] : $voucher->mac_address,
                'bytes_in'    => (int) $data['bytesIn'],
                'bytes_out'   => (int) $data['bytesOut'],
                'up_time'     => $data['uptime'],
                'status'      => 'active',
                'updated_at'  => now(),
            ];

            $activationTimestamp = $voucher->activated_at;

            // 6. Parse Activation Date from Comment if not set in DB
            if (is_null($activationTimestamp)) {
                $activationTimestamp = $this->parseActivationTimestamp($data['comment'], $voucher->id);
                if ($activationTimestamp) {
                    $updateData['activated_at'] = $activationTimestamp;
                }
            }

            // 7. Calculate Expires At if not set
            if (is_null($voucher->expires_at) && $activationTimestamp) {
                $expiresAt = $this->calculateExpiryDate($voucher, $activationTimestamp);
                if ($expiresAt) {
                    $updateData['expires_at'] = $expiresAt;
                }
            }

            $voucher->update($updateData);
            $updatedCount++;
        }

        return response()->json([
            'status' => 'success',
            'processed' => $updatedCount
        ]);
    }

    /**
     * Parse activation timestamp from a comment string.
     */
    private function parseActivationTimestamp(string $comment, int $voucherId): ?Carbon
    {
        if (preg_match('/Act:\s*([^|]+)/i', $comment, $matches)) {
            $dateStr = trim($matches[1]);
            try {
                // MikroTik format "M/d/Y H:i:s" is tricky with Carbon::parse.
                // createFromFormat is more reliable for non-standard formats.
                return str_contains($dateStr, '/')
                    ? Carbon::createFromFormat('M/d/Y H:i:s', $dateStr, config('app.timezone'))
                    : Carbon::parse($dateStr);
            } catch (\Exception $e) {
                \Log::error("MikroTik PushActiveUsers: Failed to parse date '{$dateStr}' for voucher {$voucherId}", ['exception' => $e->getMessage()]);
            }
        }
        return null;
    }

    /**
     * Calculate the expiry date based on profile validity.
     */
    private function calculateExpiryDate(Voucher $voucher, Carbon $activationTimestamp): ?Carbon
    {
        if (!$voucher->profile || !$voucher->profile->validity) {
            return null;
        }

        try {
            $validity = strtolower(trim($voucher->profile->validity));
            $expiresAt = $activationTimestamp->copy(); // Use copy to avoid mutating the original
            $amount = (int) $validity;

            if ($amount <= 0) return null;

            if (str_contains($validity, 'h')) {
                return $expiresAt->addHours($amount);
            }
            if (str_contains($validity, 'm') && !str_contains($validity, 'mo')) {
                return $expiresAt->addMinutes($amount);
            }
            // Default to days (covers 'd', 'days', or just number)
            return $expiresAt->addDays($amount);
        } catch (\Exception $e) {
            \Log::warning("Could not calculate expiry for voucher {$voucher->id}", ['validity' => $voucher->profile->validity, 'exception' => $e->getMessage()]);
        }

        return null;
    }

    public function pullProfiles(Request $request)
    {
        //changed
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
                'name'         => $p->name,
                'shared_users' => $p->shared_users,
                'rate_limit'   => $p->rate_limit,
            ];
        });

        return response()->json([
            'router_id' => $router->id,
            'count'     => $profilesJson->count(),
            'profiles'  => $profilesJson,
        ]);
    }
}
