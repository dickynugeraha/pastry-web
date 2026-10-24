@extends('layouts.store')

@section('title', 'Pesanan '.$order->order_code.' · Bunkue Pastry')

@section('content')
@php
$orderStatus = ['pending' => 'Menunggu', 'processing' => 'Diproses', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];
$paymentStatus = ['unpaid' => 'Belum dibayar', 'paid' => 'Sudah dibayar', 'failed' => 'Gagal', 'refunded' => 'Dikembalikan'];
@endphp
<section class="bunkue-container py-10 md:py-14">
    <nav aria-label="Tahapan belanja" class="flex items-center gap-2 text-xs font-medium text-stone-500">
        <a href="{{ route('cart.show') }}" class="hover:text-stone-900">Keranjang</a>
        <x-lucide-chevron-right class="size-3.5" aria-hidden="true" />
        <span>Checkout</span>
        <x-lucide-chevron-right class="size-3.5" aria-hidden="true" />
        <span class="text-stone-900" aria-current="step">Pesanan</span>
    </nav>
    <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="bunkue-kicker">Detail pesanan</p>
            <h1 class="bunkue-page-title mt-3">{{ $order->order_code }}</h1>
            <p class="mt-3 text-sm text-stone-600">Dibuat {{ $order->created_at->translatedFormat('d M Y, H:i') }}</p>
        </div>
        <span class="inline-flex rounded-full bg-[#f1eae3] px-4 py-2 text-sm font-medium text-[#775744]">{{ $order->shipping_status->label() }}</span>
    </div>

    <div class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="bunkue-card p-5 sm:p-7">
            <h2 class="text-lg font-semibold text-stone-900">Produk dipesan</h2>
            <div class="mt-5 divide-y divide-stone-200 border-y border-stone-200">
                @foreach ($order->items as $item)
                <div class="flex justify-between gap-4 py-4 text-sm">
                    <div>
                        <p class="font-medium text-stone-900">{{ $item->product_name_snapshot }}</p>
                        <p class="mt-1 text-stone-500">{{ $item->qty }} × Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                    </div>
                    <p class="shrink-0 font-semibold text-stone-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                </div>
                @endforeach
            </div>
            <div class="mt-5 flex justify-between gap-4 text-base font-semibold text-stone-900">
                <span>Total produk</span>
                <span>Rp {{ number_format($order->total, 0, ',', '.') }}</span>
            </div>
            @if ($order->notes)
            <div class="mt-7 rounded-xl bg-stone-50 p-4">
                <p class="text-xs font-semibold text-stone-700">Catatan pesanan</p>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-stone-600">{{ $order->notes }}</p>
            </div>
            @endif
        </div>
        <aside class="grid gap-4">
            @include('shop.orders.payment', ['order' => $order])
            <div class="bunkue-card p-6">
                <h2 class="text-base font-semibold text-stone-900">Penerimaan</h2>
                <p class="mt-4 text-sm font-medium text-stone-800">{{ $order->delivery_method->label() }}</p>
                @if ($order->delivery_method->value === 'delivery')
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-stone-600">{{ $order->recipient_name }}<br>{{ $order->recipient_phone }}<br>{{ $order->shipping_address }}</p>
                @elseif ($order->pickup_code)
                <p class="mt-3 text-xs text-stone-500">Kode pengambilan</p>
                <p class="mt-1 rounded-lg bg-stone-100 px-3 py-2 font-mono text-lg font-semibold tracking-widest text-stone-900">{{ $order->pickup_code }}</p>
                @endif
            </div>
            <div class="bunkue-card p-6">
                <h2 class="text-base font-semibold text-stone-900">Status</h2>
                <dl class="mt-4 grid gap-4 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-stone-500">Pesanan</dt>
                        <dd class="font-medium text-stone-900">{{ $orderStatus[$order->status] ?? ucfirst($order->status) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-stone-500">Pembayaran</dt>
                        <dd class="font-medium text-stone-900">{{ $paymentStatus[$order->payment_status] ?? ucfirst($order->payment_status) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-stone-500">Pengiriman</dt>
                        <dd class="font-medium text-stone-900">{{ $order->shipping_status->label() }}</dd>
                    </div>
                </dl>
            </div>
            <a href="{{ route('orders.index') }}" class="bunkue-button-secondary w-full">Semua pesanan</a>
        </aside>
    </div>
</section>
@endsection
