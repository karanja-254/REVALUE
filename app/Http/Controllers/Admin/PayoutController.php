<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SellerPayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(): View
    {
        return view('admin.payouts.index', [
            'payouts' => SellerPayout::query()->with(['order.listing', 'seller', 'paidBy'])->latest()->paginate(20),
        ]);
    }

    public function pay(Request $request, SellerPayout $payout): RedirectResponse
    {
        $data = $request->validate([
            'mpesa_number' => ['required', 'string', 'max:30'],
            'mpesa_receipt' => ['required', 'string', 'max:40'],
        ]);

        $payout->update([
            ...$data,
            'status' => SellerPayout::STATUS_PAID,
            'paid_by' => $request->user()->id,
            'paid_at' => now(),
        ]);

        return back()->with('status', 'M-Pesa payout recorded for '.$payout->seller->name.'.');
    }

    public function refund(Order $order): RedirectResponse
    {
        abort_unless($order->payment_status === Order::PAYMENT_PAID, 422);

        $order->update([
            'payment_status' => Order::PAYMENT_REFUNDED,
            'order_status' => Order::STATUS_CANCELLED,
        ]);

        $order->sellerPayout?->update(['status' => SellerPayout::STATUS_CANCELLED]);

        return back()->with('status', 'Order marked refunded. Record the actual Paystack refund in the dashboard.');
    }
}
