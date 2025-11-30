<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\Voucher;
use Illuminate\Http\Request;

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
        $request->validate([
            'token' => 'required|string',
            'data'  => 'required|array'
        ]);

        $router = Router::where('api_token', $request->token)->first();
        if (!$router) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        foreach ($request->data as $item) {

            $voucher = Voucher::where('username', $item['username'] ?? null)
                ->where('router_id', $router->id)
                ->first();

            if (!$voucher) continue;

            // update db fields
            $voucher->update([
                'mac_address' => $item['mac']        ?? $voucher->mac_address,
                'uptime'      => $item['uptime']     ?? null,
                'download_mb' => isset($item['download']) ? round($item['download'] / 1024 / 1024, 2) : null,
                'upload_mb'   => isset($item['upload']) ? round($item['upload'] / 1024 / 1024, 2) : null,
            ]);
        }

        return response()->json(['status' => 'success']);
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


    public function checkProfile(Request $request)
    {
        $token = $request->query('token');
        $name  = $request->query('name');

        if (! $token || ! $name) {
            return response()->json([
                'error' => 'Missing token or name',
            ], 400);
        }

        $router = Router::where('app_key', $token)->first();

        if (! $router) {
            return response()->json([
                'error' => 'Invalid token',
            ], 401);
        }

        // Adjust relation / condition as needed
        $exists = $router->profiles()
            ->where('name', $name)
            ->exists();

        return response()->json([
            'exists' => $exists,
        ]);
    }
}
