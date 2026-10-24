<?php

namespace App\Http\Controllers;

use App\Exceptions\MidtransUnavailableException;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MidtransPaymentController extends Controller
{
    public function store(Request $request, Order $order, PaymentService $payments): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $order);
        try {
            $order = $payments->startSession($order);
        } catch (MidtransUnavailableException) {
            throw ValidationException::withMessages(['payment' => 'Midtrans belum tersedia. Pesanan tersimpan; silakan coba lagi.']);
        }

        return $request->expectsJson() ? response()->json(['token' => $order->snap_token]) : back()->with('success', 'Sesi pembayaran siap. Pilih Bayar sekarang.');
    }

    public function sync(Request $request, Order $order, PaymentService $payments): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $order);
        try {
            $order = $payments->sync($order);
        } catch (MidtransUnavailableException) {
            throw ValidationException::withMessages(['payment' => 'Status Midtrans belum dapat diperiksa. Silakan coba lagi.']);
        }

        return $request->expectsJson() ? response()->json(['data' => $order]) : back()->with('success', 'Status pembayaran diperiksa.');
    }

    public function notification(Request $request, PaymentService $payments): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'string', 'max:255'], 'status_code' => ['required', 'string', 'regex:/^\d{3}$/D'],
            'gross_amount' => ['required', 'string', 'max:20'], 'signature_key' => ['required', 'string', 'size:128'],
            'transaction_id' => ['required', 'string', 'max:255'], 'transaction_status' => ['required', 'string'],
            'payment_type' => ['sometimes', 'string', 'max:255'], 'fraud_status' => ['sometimes', 'string'],
            'merchant_id' => ['sometimes', 'string'], 'currency' => ['sometimes', 'string'],
        ]);
        try {
            $payments->notify($data);
        } catch (MidtransUnavailableException) {
            return response()->json(['message' => 'Payment verification unavailable; retry notification.'], 503);
        }

        return response()->json(['message' => 'Notification processed.']);
    }
}
