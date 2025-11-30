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
        // 1. Authenticate Router (Check Header or Query)
        $token = $request->bearerToken() ?? $request->query('token');

        // Note: Using 'app_key' to match your previous scripts
        $router = Router::where('app_key', $token)->first();

        if (!$router) {
            \Log::warning('Invalid token attempt in pushActiveUsers', ['token' => $token]);

            return response()->json(['error' => 'Invalid token'], 401);
        }

        // 2. Get Raw Body Content (Since script sends raw text)
        $content = $request->getContent();

        if (empty($content)) {
            return response()->json(['status' => 'no_data']);
        }

        // 3. Process Lines
        $lines = explode("\n", $content);
        $updatedCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Parse CSV format: username;mac;bin;bout;uptime;comment
            $parts = explode(';', $line);

            // Safety check for column count
            if (count($parts) < 6) continue;

            [$username, $mac, $bytesIn, $bytesOut, $uptime, $comment] = $parts;

            // 4. Find Voucher
            $voucher = Voucher::where('username', $username)
                ->where('router_id', $router->id)
                ->first();

            if ($voucher) {
                $updateData = [
                    'mac_address' => !empty($mac) ? $mac : $voucher->mac_address,
                    'bytes_in'    => (int) $bytesIn,
                    'bytes_out'   => (int) $bytesOut,
                    'up_time'     => $uptime,
                    'status'      => 'active', // Mark active as we received live stats
                ];

                // 5. Parse Activation Date from Comment (if not already set)
                // Logic: Looks for "Act: nov/30/2025..." in the comment string
                if (is_null($voucher->activated_at) && preg_match('/Act:\s*([a-zA-Z0-9\/\s:]+)/', $comment, $matches)) {
                    try {
                        // MikroTik dates (e.g., nov/30/2025) are parseable by Carbon
                        $updateData['activated_at'] = Carbon::parse($matches[1]);
                    } catch (\Exception $e) {
                        // Log error if date format is weird, but don't stop
                    }
                }

                $voucher->update($updateData);
                $updatedCount++;
            }
        }

        return response()->json([
            'status' => 'success',
            'processed' => $updatedCount
        ]);
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
