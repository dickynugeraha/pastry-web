@extends('layouts.admin')

@section('title', 'Kelola '.$order->order_code)

@section('content')
@php
$shippingOptions = $order->delivery_method === \App\Enums\DeliveryMethod::DELIVERY
? \App\Enums\ShippingStatus::forDelivery()
: \App\Enums\ShippingStatus::forPickup();
@endphp
<a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-[#775744] hover:underline"><x-lucide-arrow-left class="size-4" aria-hidden="true" /> Pesanan</a>
<div class="mt-5 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-sm text-stone-500">{{ $order->created_at->format('d M Y, H:i') }}</p>
        <h1 class="mt-2 text-3xl text-stone-900">{{ $order->order_code }}</h1>
        <p class="mt-2 text-sm text-stone-600">{{ $order->user?->name }} · {{ $order->user?->email }}</p>
    </div>
    @include('admin.partials.order-status-badge', ['status' => $order->shipping_status])
</div>
<div class="mt-8 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
    <div class="bunkue-card p-6">
        <h2 class="text-base font-semibold text-stone-900">Item pesanan</h2>
        <div class="mt-4 divide-y divide-stone-100 border-y border-stone-200">
            @foreach ($order->items as $item)
            <div class="flex justify-between gap-4 py-4 text-sm">
                <div>
                    <p class="font-medium">{{ $item->product_name_snapshot }}</p>
                    <p class="mt-1 text-stone-500">{{ $item->qty }} × Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                </div>
                <p class="shrink-0 font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
            </div>
            @endforeach
        </div>
        <div class="mt-5 flex justify-between font-semibold"><span>Total</span><span>Rp {{ number_format($order->total, 0, ',', '.') }}</span></div>
        <div class="mt-7 rounded-xl bg-stone-50 p-4 text-sm leading-6 text-stone-600">
            <p class="font-semibold text-stone-900">{{ $order->delivery_method->label() }}</p>
            @if ($order->delivery_method->value === 'delivery')
            <p class="mt-2 whitespace-pre-line">{{ $order->recipient_name }}<br>{{ $order->recipient_phone }}<br>{{ $order->shipping_address }}</p>
            @elseif ($order->pickup_code)
            <p class="mt-2">Kode pengambilan: <strong>{{ $order->pickup_code }}</strong></p>
            @endif
        </div>
    </div>
    <aside class="bunkue-card p-6">
        <h2 class="text-base font-semibold text-stone-900">Perbarui status</h2>
        <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-5 grid gap-4">
            @csrf
            @method('PATCH')
            <div>
                <label for="status" class="mb-2 block text-sm font-medium text-stone-700">Status pesanan</label>
                <select id="status" name="status" class="w-full">
                    @foreach (['pending' => 'Menunggu', 'processing' => 'Diproses', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)
                    <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="shipping_status" class="mb-2 block text-sm font-medium text-stone-700">Status pengiriman</label>
                <select id="shipping_status" name="shipping_status" class="w-full">
                    @foreach ($shippingOptions as $shipping)
                    <option value="{{ $shipping->value }}" @selected($order->shipping_status === $shipping)>{{ $shipping->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bunkue-button-primary w-full">Simpan status</button>
        </form>
    </aside>
</div>
@endsection