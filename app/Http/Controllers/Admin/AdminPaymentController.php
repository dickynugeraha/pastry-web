<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewPaymentProofRequest;
use App\Http\Requests\UpdateManualPaymentRequest;
use App\Models\Order;
use App\Models\PaymentProof;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AdminPaymentController extends Controller
{
    public function update(UpdateManualPaymentRequest $request, Order $order, PaymentService $payments): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $payments->overrideManual($order, $request->user(), $data['payment_status'], $data['note']);

        return $request->expectsJson() ? response()->json(['data' => $order->refresh()]) : back()->with('success', 'Status pembayaran diperbarui dan dicatat.');
    }

    public function review(ReviewPaymentProofRequest $request, Order $order, PaymentProof $paymentProof, PaymentService $payments): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $payments->reviewProof($order, $paymentProof, $request->user(), $data['action'], $data['rejection_reason'] ?? null);

        return $request->expectsJson() ? response()->json(['data' => $paymentProof->refresh()]) : back()->with('success', 'Bukti pembayaran telah ditinjau.');
    }
}
