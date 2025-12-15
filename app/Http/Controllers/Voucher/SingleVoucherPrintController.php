<?php

namespace App\Http\Controllers\Voucher;

use App\Http\Controllers\Controller;
use App\Models\Voucher;

class SingleVoucherPrintController extends Controller
{
    /**
     * Display printer-friendly markup for a single voucher.
     */
    public function __invoke(Voucher $voucher)
    {
        $voucher->load(['profile', 'router.voucherTemplate']);

        if (! $voucher->router) {
            abort(404, 'Router not found for this voucher.');
        }

        // Authorization: Verify user has access to this voucher's router
        $user = auth()->user();
        try {
            $user->getAuthorizedRouter($voucher->router_id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(403, 'You are not authorized to print this voucher.');
        }

        return view('components.vouchers.print', [
            'router' => $voucher->router,
            'vouchers' => collect([$voucher]),
        ]);
    }
}
