<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentProofRequest;
use App\Models\Order;
use App\Models\PaymentProof;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofController extends Controller
{
    public function store(StorePaymentProofRequest $request, Order $order, PaymentService $payments): JsonResponse|RedirectResponse
    {
        $proof = $payments->uploadProof($order, $request->user(), $request->file('proof'));

        return $request->expectsJson() ? response()->json(['data' => $proof], 201) : back()->with('success', 'Bukti terkirim. Menunggu verifikasi admin.');
    }

    public function show(Order $order, PaymentProof $paymentProof): StreamedResponse
    {
        $this->authorize('view', $order);
        abort_unless($paymentProof->order_id === $order->id, 404);
        abort_unless($paymentProof->disk === 'local' && Storage::disk('local')->exists($paymentProof->file_path), 404);
        $extension = match ($paymentProof->mime_type) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf', default => abort(404)
        };

        return Storage::disk('local')->response($paymentProof->file_path, 'bukti-'.$paymentProof->id.'.'.$extension, [
            'Content-Type' => $paymentProof->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ], $extension === 'pdf' ? 'attachment' : 'inline');
    }
}
