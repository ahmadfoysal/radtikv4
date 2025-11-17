<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\Voucher;
use Illuminate\Http\Request;

class MikrotikController extends Controller
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
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $vouchers = $router->vouchers()
            ->where('is_radius', false)
            ->where('status', 'inactive')
            ->get()
            ->map(function ($v) {
                return [
                    'username'    => $v->username,
                    'password'    => $v->password,
                    'profile'     => $v->router_profile,
                    'validity'    => $v->expires_at ? $v->expires_at->diffInMinutes($v->activated_at) : null,
                    'comments'    => 'RADTik-' . $v->batch,
                ];
            });

        return response()->json([
            'router_id' => $router->id,
            'count'     => $vouchers->count(),
            'vouchers'  => $vouchers,
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
                    'comments'    => 'RADTik-' . $v->batch,
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
    public function pushUsage(Request $request)
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
}
