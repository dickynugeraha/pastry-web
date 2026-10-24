<section class="bunkue-card mt-6 grid gap-5 p-6">
    <h2 class="text-lg font-semibold">Pembayaran · {{ $order->payment_status }}</h2>
    <p class="text-sm">{{ $order->payment_channel?->label() ?? 'Kanal belum tercatat' }} · {{ $order->manual_payment_details['provider'] ?? $order->payment_type ?? '—' }}</p>
    @if ($errors->any())<p role="alert" class="text-sm text-red-700">{{ $errors->first() }}</p>@endif
    @if ($order->payment_channel === \App\Enums\PaymentChannel::MANUAL)
        <p class="text-sm">{{ $order->manual_payment_details['destination_number'] ?? '' }} · {{ $order->manual_payment_details['account_name'] ?? '' }}</p>
        @if ($order->payment_status === 'unpaid' && $order->payment_due_at?->isPast())<p class="text-sm text-amber-800">Pembayaran melewati batas waktu. Periksa sebelum membatalkan.</p>@endif
        @foreach ($order->paymentProofs as $proof)
        <div class="grid gap-3 rounded-xl border border-stone-200 p-4 text-sm">
            <a href="{{ route('admin.orders.payment-proofs.show', [$order, $proof]) }}" class="font-medium underline">Lihat / unduh bukti #{{ $proof->id }}</a>
            <p>{{ $proof->review_status }} · {{ $proof->reviewedBy?->name }} {{ $proof->reviewed_at?->format('d M Y H:i') }}</p>
            @if ($proof->rejection_reason)<p>{{ $proof->rejection_reason }}</p>@endif
            @if ($proof->review_status === 'pending' && $order->acceptsPaymentProof())
            <div class="flex flex-wrap items-start gap-3">
                <form method="POST" action="{{ route('admin.orders.payment-proofs.update', [$order, $proof]) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="accept"><button type="submit" class="bunkue-button-primary">Terima bukti</button></form>
                <form method="POST" action="{{ route('admin.orders.payment-proofs.update', [$order, $proof]) }}" class="grid gap-2">@csrf @method('PATCH')<input type="hidden" name="action" value="reject"><label for="rejection-{{ $proof->id }}">Alasan penolakan</label><input id="rejection-{{ $proof->id }}" name="rejection_reason" required maxlength="2000"><button type="submit" class="bunkue-button-secondary">Tolak bukti</button></form>
            </div>
            @endif
        </div>
        @endforeach
        @if ($order->status === 'pending' && $order->shipping_status->value === 'pending')
        <form method="POST" action="{{ route('admin.orders.manual-payment.update', $order) }}" class="grid max-w-lg gap-3 border-t border-stone-200 pt-4">
            @csrf @method('PATCH')
            <h3 class="font-semibold">Konfirmasi manual di luar website</h3>
            <label for="payment_status">Status pembayaran</label>
            <select id="payment_status" name="payment_status"><option value="paid" @selected($order->payment_status === 'paid')>Sudah dibayar</option><option value="unpaid" @selected($order->payment_status === 'unpaid')>Belum dibayar</option></select>
            <label for="payment_note">Catatan / alasan (wajib)</label><textarea id="payment_note" name="note" required minlength="3" maxlength="2000"></textarea>
            <button type="submit" class="bunkue-button-primary">Simpan konfirmasi</button>
        </form>
        @endif
    @elseif ($order->payment_channel === \App\Enums\PaymentChannel::MIDTRANS)
        <p class="text-sm">Order Midtrans: {{ $order->midtrans_order_id }} · Transaksi berhasil: {{ $order->transaction_id ?? '—' }}</p>
        <form method="POST" action="{{ route('admin.orders.midtrans.sync', $order) }}">@csrf<button type="submit" class="bunkue-button-secondary">Sinkronkan Midtrans</button></form>
    @endif
    @foreach ($order->payments as $payment)
    <p class="text-xs text-stone-600">{{ $payment->provider }} · {{ $payment->transaction_id ?? $payment->payment_type }} · {{ $payment->transaction_status }} · {{ $payment->confirmedBy?->name }} {{ $payment->confirmed_at?->format('d M Y H:i') }}</p>
    @if ($payment->transaction_status === 'partial_refund')
    <p class="text-xs text-amber-800">Refund sebagian: {{ $payment->raw_notification['refund_amount'] ?? 'periksa dashboard Midtrans' }}. Laporan menggunakan pendapatan bruto.</p>
    @endif
    @endforeach
    <div class="grid gap-2 border-t border-stone-200 pt-4 text-xs text-stone-600">
        <h3 class="text-sm font-semibold text-stone-900">Riwayat pembayaran</h3>
        @forelse ($order->paymentStatusLogs as $log)
        <p>{{ $log->created_at->format('d M Y H:i') }} · {{ $log->from_status }} → {{ $log->to_status }} · {{ $log->source }} · {{ $log->changedBy?->name ?? 'Sistem' }}<br>{{ $log->note }}</p>
        @empty<p>Belum ada perubahan status pembayaran.</p>@endforelse
    </div>
</section>
