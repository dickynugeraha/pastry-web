<section class="bunkue-card grid gap-4 p-6">
    <h2 class="text-base font-semibold text-stone-900">Pembayaran</h2>
    <p class="text-sm">{{ $order->payment_channel?->label() ?? 'Kanal belum tercatat' }} · {{ $paymentStatus[$order->payment_status] ?? $order->payment_status }}</p>
    @if ($errors->any())
    <div role="alert" class="text-sm text-red-700">{{ $errors->first() }}</div>
    @endif
    @if ($order->payment_channel === \App\Enums\PaymentChannel::MANUAL)
        <div class="rounded-xl bg-stone-50 p-4 text-sm leading-6">
            <p class="font-semibold">{{ $order->manual_payment_details['provider'] ?? '' }}</p>
            <p class="font-mono text-lg">{{ $order->manual_payment_details['destination_number'] ?? '' }}</p>
            <p>{{ $order->manual_payment_details['account_name'] ?? '' }}</p>
            <p>Nominal: Rp {{ number_format($order->total, 0, ',', '.') }}</p>
            @if ($order->payment_due_at)
            <p class="mt-2 text-xs">Batas pembayaran: {{ $order->payment_due_at->format('d M Y H:i') }}</p>
            @if ($order->payment_status === 'unpaid' && $order->payment_due_at->isPast())
            <p class="text-xs text-amber-800">Melewati batas pembayaran. Hubungi admin jika sudah transfer.</p>
            @endif
            @endif
        </div>
        @if ($order->acceptsPaymentProof())
        <form method="POST" action="{{ route('orders.payment-proofs.store', $order) }}" enctype="multipart/form-data" class="grid gap-3">
            @csrf
            <label for="proof" class="text-sm font-medium">Unggah bukti pembayaran</label>
            <input id="proof" type="file" name="proof" accept="image/jpeg,image/png,application/pdf" required class="w-full text-xs">
            <p class="text-xs text-stone-500">JPG, PNG, PDF. Maksimal {{ number_format(config('payments.proof_max_kb') / 1024, 1) }} MB.</p>
            <button class="bunkue-button-primary" type="submit">Kirim bukti</button>
        </form>
        @endif
        @foreach ($order->paymentProofs as $proof)
        <div class="border-t border-stone-200 pt-3 text-sm">
            <a href="{{ route('orders.payment-proofs.show', [$order, $proof]) }}" class="font-medium underline">Bukti #{{ $proof->id }}</a>
            <p class="mt-1">{{ ['pending' => 'Menunggu verifikasi admin', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', 'superseded' => 'Digantikan bukti terbaru'][$proof->review_status] ?? $proof->review_status }}</p>
            @if ($proof->rejection_reason)<p class="mt-1 text-red-700">{{ $proof->rejection_reason }}</p>@endif
        </div>
        @endforeach
    @elseif ($order->payment_channel === \App\Enums\PaymentChannel::MIDTRANS)
        @if ($order->status === 'pending' && in_array($order->payment_status, ['unpaid', 'failed'], true) && $order->payment_due_at?->isFuture() && config('payments.midtrans.enabled'))
            @if ($order->snap_token)
            <button type="button" data-midtrans-pay data-token="{{ $order->snap_token }}" data-sync-url="{{ route('orders.midtrans.sync', $order) }}" class="bunkue-button-primary">Bayar sekarang</button>
            @else
            <form method="POST" action="{{ route('orders.midtrans.session', $order) }}">@csrf<button type="submit" class="bunkue-button-primary w-full">Coba lagi pembayaran</button></form>
            @endif
        @endif
        <form method="POST" action="{{ route('orders.midtrans.sync', $order) }}">@csrf<button type="submit" class="bunkue-button-secondary w-full">Periksa status pembayaran</button></form>
        <p data-payment-message role="status" class="text-xs text-stone-600">Status dikonfirmasi melalui Midtrans. Jika pembayaran belum muncul, periksa kembali.</p>
        @push('scripts')
        <script src="{{ config('payments.midtrans.snap_script_url') }}" data-client-key="{{ config('payments.midtrans.client_key') }}"></script>
        @endpush
    @endif
</section>
