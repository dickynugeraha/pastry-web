<?php

namespace App\Services;

use App\Enums\PaymentChannel;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PaymentService
{
    public function __construct(private MidtransService $midtrans) {}

    public function startSession(Order $order): Order
    {
        return Cache::lock('payment-session-'.$order->id, 60)->block(2, function () use ($order): Order {
            $order->refresh();
            if ($order->payment_channel !== PaymentChannel::MIDTRANS || ! in_array($order->payment_status, ['unpaid', 'failed'], true) || $order->status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'Pesanan ini tidak dapat dibayar.']);
            }
            if ($order->payment_due_at?->isPast()) {
                throw ValidationException::withMessages(['payment' => 'Sesi pembayaran berakhir. Periksa status pembayaran.']);
            }
            if ($order->snap_token) {
                return $order;
            }
            $token = $this->midtrans->createSession($order->loadMissing('user'));
            $order->update(['snap_token' => $token]);

            return $order->refresh();
        });
    }

    public function uploadProof(Order $order, User $user, UploadedFile $file): PaymentProof
    {
        abort_unless($order->user_id === $user->id, 403);
        $disk = (string) config('payments.proof_disk');
        abort_unless($disk === 'local', 503, 'Payment proofs require private local storage.');
        $path = $file->store('payment-proofs/'.$order->id, $disk);
        if ($path === false) {
            throw new RuntimeException('Bukti pembayaran tidak dapat disimpan.');
        }
        try {
            return DB::transaction(function () use ($order, $user, $file, $disk, $path): PaymentProof {
                $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if (! $locked->acceptsPaymentProof()) {
                    throw ValidationException::withMessages(['proof' => 'Pesanan ini tidak menerima bukti pembayaran.']);
                }
                $locked->paymentProofs()->where('review_status', 'pending')->update(['review_status' => 'superseded']);

                return $locked->paymentProofs()->create([
                    'uploaded_by_user_id' => $user->id,
                    'manual_payment_method' => $locked->manual_payment_method->value,
                    'disk' => $disk,
                    'file_path' => $path,
                    'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'review_status' => 'pending',
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }

    public function reviewProof(Order $order, PaymentProof $proof, User $admin, string $action, ?string $reason): void
    {
        abort_unless($admin->isAdmin(), 403);
        DB::transaction(function () use ($order, $proof, $admin, $action, $reason): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $proof = $locked->paymentProofs()->whereKey($proof->id)->lockForUpdate()->firstOrFail();
            if (! $locked->acceptsPaymentProof() || $proof->review_status !== 'pending') {
                throw ValidationException::withMessages(['proof' => 'Bukti ini tidak dapat ditinjau lagi.']);
            }
            $proof->update([
                'review_status' => $action === 'accept' ? 'accepted' : 'rejected',
                'reviewed_by_user_id' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $action === 'reject' ? $reason : null,
            ]);
            if ($action === 'accept') {
                $locked->paymentProofs()->where('review_status', 'pending')->update(['review_status' => 'superseded']);
                $this->confirmManual($locked, $admin, 'paid', 'manual_proof', 'Bukti pembayaran #'.$proof->id.' diterima.');
            } else {
                $this->audit($locked, 'unpaid', 'manual_proof', $admin, $reason, context: ['proof_id' => $proof->id, 'review' => 'rejected']);
            }
        });
    }

    public function overrideManual(Order $order, User $admin, string $status, string $note): void
    {
        abort_unless($admin->isAdmin(), 403);
        DB::transaction(function () use ($order, $admin, $status, $note): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_channel !== PaymentChannel::MANUAL || ! in_array($locked->payment_status, ['paid', 'unpaid'], true)
                || $locked->status !== 'pending' || $locked->shipping_status->value !== 'pending') {
                throw ValidationException::withMessages(['payment_status' => 'Status pembayaran hanya dapat diubah untuk pesanan manual yang masih menunggu.']);
            }
            if ($locked->payment_status !== $status) {
                $this->confirmManual($locked, $admin, $status, 'admin_manual', $note);
            }
        });
    }

    private function confirmManual(Order $order, User $admin, string $status, string $source, string $note): void
    {
        $this->audit($order, $status, $source, $admin, $note);
        $order->update(['payment_status' => $status, 'paid_at' => $status === 'paid' ? now() : null]);
        $order->payments()->create([
            'provider' => 'manual',
            'payment_type' => $order->manual_payment_method->value,
            'transaction_status' => $status,
            'gross_amount' => $order->total,
            'confirmed_by_user_id' => $admin->id,
            'confirmed_at' => now(),
            'note' => $note,
        ]);
        if ($status === 'paid') {
            $order->paymentProofs()->where('review_status', 'pending')->update(['review_status' => 'superseded']);
        }
    }

    /** @param array<string, mixed> $notification */
    public function notify(array $notification): void
    {
        $key = (string) config('payments.midtrans.server_key');
        abort_unless($this->midtrans->available() && $key !== '', 503);
        $signature = hash('sha512', $notification['order_id'].$notification['status_code'].$notification['gross_amount'].$key);
        abort_unless(hash_equals($signature, $notification['signature_key']), 403, 'Invalid signature.');
        $order = Order::query()->where('midtrans_order_id', $notification['order_id'])->firstOrFail();
        $this->validateNotification($order, $notification);
        $verified = $this->midtrans->status($notification['transaction_id']);
        abort_if($verified === null, 503, 'Transaction verification is not yet available.');
        abort_unless(($verified['transaction_id'] ?? null) === $notification['transaction_id'], 422, 'Transaction identity mismatch.');
        $this->applyMidtrans($order, $verified, 'midtrans_webhook');
    }

    public function sync(Order $order): Order
    {
        if ($order->payment_channel !== PaymentChannel::MIDTRANS) {
            throw ValidationException::withMessages(['payment' => 'Pesanan ini tidak menggunakan Midtrans.']);
        }
        $status = $this->midtrans->status($order->midtrans_order_id);
        if ($status !== null) {
            $this->applyMidtrans($order, $status, 'midtrans_sync');
        }

        $order->refresh();
        if ($order->payment_due_at?->isPast() && $order->status === 'pending' && ! in_array($order->payment_status, ['paid', 'refunded'], true)) {
            $terminal = $status !== null && in_array($status['transaction_status'], ['expire', 'cancel'], true);
            if ($status === null && $order->snap_token && $this->midtrans->cancelSession($order->snap_token)) {
                $latest = $this->midtrans->status($order->midtrans_order_id);
                if ($latest !== null) {
                    $this->applyMidtrans($order, $latest, 'midtrans_sync');
                }
                $terminal = $latest === null || in_array($latest['transaction_status'], ['expire', 'cancel'], true);
            }
            if ($terminal) {
                $this->cancelExpiredOrder(
                    $order,
                    PaymentChannel::MIDTRANS,
                    'Sesi Midtrans terverifikasi dibatalkan setelah batas pembayaran.',
                );
            }
        }

        return $order->refresh();
    }

    public function expireManualOrder(Order $order): void
    {
        $this->cancelExpiredOrder(
            $order,
            PaymentChannel::MANUAL,
            'Pesanan otomatis dibatalkan karena belum dibayar sampai batas waktu.',
        );
    }

    private function cancelExpiredOrder(Order $order, PaymentChannel $paymentChannel, string $note): void
    {
        DB::transaction(function () use ($order, $paymentChannel, $note): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_channel !== $paymentChannel
                || $locked->payment_due_at === null
                || $locked->payment_due_at->isFuture()
                || $locked->status !== 'pending'
                || in_array($locked->payment_status, ['paid', 'refunded'], true)) {
                return;
            }

            foreach ($locked->items()->orderBy('product_id')->lockForUpdate()->get() as $item) {
                if ($item->product_id !== null) {
                    Product::query()->whereKey($item->product_id)->lockForUpdate()->first()?->increment('stock', $item->qty);
                }
            }

            if ($locked->payment_status !== 'failed') {
                $this->audit($locked, 'failed', 'system', note: $note);
            }

            $locked->paymentProofs()->where('review_status', 'pending')->update(['review_status' => 'superseded']);
            $locked->update(['status' => 'cancelled', 'shipping_status' => 'cancelled', 'payment_status' => 'failed']);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    private function validateNotification(Order $order, array $data): void
    {
        abort_unless($order->payment_channel === PaymentChannel::MIDTRANS && ($data['order_id'] ?? null) === $order->midtrans_order_id, 422);
        $amount = (string) ($data['gross_amount'] ?? '');
        abort_unless(preg_match('/^\d{1,12}(\.\d{1,2})?$/D', $amount) === 1 && number_format((float) $amount, 2, '.', '') === $order->total, 422, 'Amount mismatch.');
        abort_unless(is_string($data['transaction_id'] ?? null) && $data['transaction_id'] !== '' && strlen($data['transaction_id']) <= 255, 422);
        abort_unless(in_array($data['transaction_status'] ?? null, ['pending', 'capture', 'settlement', 'deny', 'cancel', 'expire', 'failure', 'refund', 'partial_refund'], true), 422);
        abort_unless(! isset($data['currency']) || $data['currency'] === 'IDR', 422);
        abort_unless(! isset($data['fraud_status']) || in_array($data['fraud_status'], ['accept', 'challenge', 'deny'], true), 422);
        $merchant = (string) config('payments.midtrans.merchant_id');
        abort_unless(! isset($data['merchant_id']) || $merchant === '' || $data['merchant_id'] === $merchant, 422);
    }

    /** @param array<string, mixed> $data */
    private function applyMidtrans(Order $order, array $data, string $source): void
    {
        $this->validateNotification($order, $data);
        DB::transaction(function () use ($order, $data, $source): void {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->where('transaction_id', $data['transaction_id'])->lockForUpdate()->first();
            abort_if($payment !== null && $payment->order_id !== $order->id, 422);
            $incoming = $data['transaction_status'];
            $previous = $payment?->transaction_status;
            $previousRefundAmount = $payment?->raw_notification['refund_amount'] ?? null;
            $rank = ['pending' => 0, 'deny' => 1, 'cancel' => 1, 'expire' => 1, 'failure' => 1, 'capture' => 2, 'settlement' => 3, 'partial_refund' => 4, 'refund' => 5];
            $previousRank = $previous === 'capture' ? match ($payment?->fraud_status) {
                'accept' => 2, 'deny' => 1, default => 0
            } : ($rank[$previous] ?? 0);
            $incomingRank = $incoming === 'capture' ? match ($data['fraud_status'] ?? null) {
                'accept' => 2, 'deny' => 1, default => 0
            } : $rank[$incoming];
            if ($previous !== null && $previousRank > $incomingRank) {
                return;
            }
            $safe = Arr::only($data, ['order_id', 'transaction_id', 'transaction_status', 'payment_type', 'fraud_status', 'gross_amount', 'status_code', 'currency', 'refund_amount']);
            $payment ??= new Payment(['order_id' => $order->id, 'transaction_id' => $data['transaction_id']]);
            $payment->fill([
                'provider' => 'midtrans',
                'midtrans_order_id' => $order->midtrans_order_id,
                'payment_type' => $data['payment_type'] ?? null,
                'transaction_status' => $incoming,
                'fraud_status' => $data['fraud_status'] ?? null,
                'gross_amount' => $data['gross_amount'],
                'raw_notification' => $safe,
            ]);
            $payment->save();
            $success = $incoming === 'settlement' || $incoming === 'partial_refund' || ($incoming === 'capture' && ($data['fraud_status'] ?? null) === 'accept');
            $target = $order->payment_status;
            if ($success && $target !== 'refunded') {
                $target = 'paid';
            } elseif ($incoming === 'refund' && ($order->transaction_id === null || $order->transaction_id === $data['transaction_id'])) {
                $target = 'refunded';
            } elseif ((in_array($incoming, ['deny', 'cancel', 'expire', 'failure'], true) || ($incoming === 'capture' && ($data['fraud_status'] ?? null) === 'deny')) && ! in_array($target, ['paid', 'refunded'], true)) {
                $target = 'failed';
            } elseif (in_array($incoming, ['pending', 'capture'], true) && ! in_array($target, ['paid', 'refunded'], true)) {
                $target = 'unpaid';
            }
            if ($target !== $order->payment_status) {
                $this->audit($order, $target, $source, externalId: $data['transaction_id']);
                $order->payment_status = $target;
            }
            if ($incoming === 'partial_refund' && ($previous !== 'partial_refund' || $previousRefundAmount !== ($data['refund_amount'] ?? null))) {
                $this->audit($order, $target, $source, note: 'Refund sebagian dicatat; status tetap paid. Laporan menampilkan pendapatan bruto.', externalId: $data['transaction_id'], context: Arr::only($data, ['refund_amount']));
            }
            if ($success && $target === 'paid') {
                $order->paid_at ??= now();
                $order->transaction_id = $data['transaction_id'];
                $order->payment_type = $data['payment_type'] ?? null;
                $order->fraud_status = $data['fraud_status'] ?? null;
            }
            $order->save();
        });
    }

    /** @param array<string, mixed> $context */
    private function audit(Order $order, string $target, string $source, ?User $admin = null, ?string $note = null, ?string $externalId = null, array $context = []): void
    {
        $order->paymentStatusLogs()->create([
            'from_status' => $order->payment_status,
            'to_status' => $target,
            'source' => $source,
            'changed_by_user_id' => $admin?->id,
            'external_transaction_id' => $externalId,
            'note' => $note,
            'context' => $context,
        ]);
    }
}
