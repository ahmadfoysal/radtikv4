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

        if (!$voucher->router) {
            abort(404);
        }

        return view('components.vouchers.print', [
            'router' => $voucher->router,
            'vouchers' => collect([$voucher]),
        ]);
    }
}
