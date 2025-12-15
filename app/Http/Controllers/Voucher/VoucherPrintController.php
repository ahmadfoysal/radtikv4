<?php

namespace App\Http\Controllers\Voucher;

use App\Http\Controllers\Controller;
use App\Models\Router;
use Illuminate\Http\Request;

class VoucherPrintController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'router_id' => 'required|integer|exists:routers,id',
            'batch' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:inactive,active,expired,all',
        ]);

        // Authorization: Verify user has access to this router
        $user = auth()->user();
        try {
            $router = $user->getAuthorizedRouter($data['router_id']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(403, 'You are not authorized to print vouchers for this router.');
        }

        $router->load(['voucherTemplate']);

        $vouchers = $router->vouchers()
            ->with('profile')
            ->when($data['batch'] ?? null, fn ($q) => $q->where('batch', $data['batch']))
            ->when(($data['status'] ?? 'all') !== 'all', fn ($q) => $q->where('status', $data['status']))
            ->orderBy('id', 'desc')
            ->get();

        if ($vouchers->isEmpty()) {
            return redirect()->route('vouchers.bulk-manager')
                ->with('error', 'No vouchers to print for the selected filters.');
        }

        return view('components.vouchers.print', [
            'router' => $router,
            'vouchers' => $vouchers,
        ]);
    }
}
