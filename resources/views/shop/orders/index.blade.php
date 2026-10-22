@extends('layouts.store')

@section('title', 'Pesanan Saya · Bunkue Pastry')

@section('content')
<section class="bunkue-container py-10 md:py-14">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="bunkue-kicker">Akun Anda</p>
            <h1 class="bunkue-page-title mt-3">Pesanan saya</h1>
            <p class="mt-3 text-sm text-stone-600">Lihat status dan detail pesanan yang pernah Anda buat.</p>
        </div>
        <a href="{{ route('products.index') }}" class="bunkue-button-secondary">Belanja lagi</a>
    </div>

    @if ($orders->count() > 0)
    <div class="mt-8 grid gap-3">
        @foreach ($orders as $order)
        <a href="{{ route('orders.show', $order) }}" class="bunkue-card group flex flex-col gap-4 p-5 transition hover:border-stone-300 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div>
                <p class="text-xs text-stone-500">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</p>
                <h2 class="mt-2 text-base font-semibold text-stone-900">{{ $order->order_code }}</h2>
                <p class="mt-1 text-sm text-stone-600">{{ $order->items->sum('qty') }} item · {{ $order->delivery_method->label() }}</p>
            </div>
            <div class="flex items-center justify-between gap-5 sm:justify-end">
                <div class="sm:text-right">
                    <span class="inline-flex rounded-full bg-[#f1eae3] px-3 py-1 text-xs font-medium text-[#775744]">{{ $order->shipping_status->label() }}</span>
                    <p class="mt-2 text-sm font-semibold text-stone-900">Rp {{ number_format($order->total, 0, ',', '.') }}</p>
                </div>
                <x-lucide-chevron-right class="size-5 text-stone-400 transition group-hover:translate-x-1" aria-hidden="true" />
            </div>
        </a>
        @endforeach
    </div>
    <div class="mt-8">{{ $orders->links() }}</div>
    @else
    <div class="bunkue-card mt-8 px-6 py-16 text-center">
        <x-lucide-receipt-text class="mx-auto size-10 text-stone-400" aria-hidden="true" />
        <h2 class="mt-5 font-serif text-2xl text-stone-900">Belum ada pesanan</h2>
        <p class="mt-2 text-sm text-stone-600">Pesanan akan muncul di sini setelah Anda checkout.</p>
        <a href="{{ route('products.index') }}" class="bunkue-button-primary mt-6">Mulai belanja</a>
    </div>
    @endif
</section>
@endsection