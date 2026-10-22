@extends('layouts.admin')

@section('title', 'Laporan Pesanan · Admin')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="bunkue-kicker">Laporan toko</p>
        <h1 class="mt-2 text-3xl text-stone-900">Laporan pesanan</h1>
        <p class="mt-2 text-sm text-stone-600">Ringkasan penjualan sesuai periode pilihan.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.orders.download', request()->query()) }}" class="bunkue-button-secondary"><x-lucide-download class="size-4" aria-hidden="true" /> Unduh CSV</a>
        <a href="{{ route('admin.reports.orders.download.pdf', request()->query()) }}" class="bunkue-button-secondary"><x-lucide-file-down class="size-4" aria-hidden="true" /> Unduh PDF</a>
    </div>
</div>
<div class="mt-7">@include('admin.partials.report-filter', ['action' => route('admin.reports.orders')])</div>
<div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="bunkue-card p-5">
        <p class="text-sm text-stone-500">Pesanan</p>
        <p class="mt-3 text-2xl font-semibold">{{ $summary['total_orders'] }}</p>
    </div>
    <div class="bunkue-card p-5">
        <p class="text-sm text-stone-500">Total pendapatan</p>
        <p class="mt-3 text-2xl font-semibold">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</p>
        <p class="mt-1 text-xs text-stone-500">Tanpa pesanan dibatalkan</p>
    </div>
    <div class="bunkue-card p-5">
        <p class="text-sm text-stone-500">Rata-rata</p>
        <p class="mt-3 text-2xl font-semibold">Rp {{ number_format($summary['average_order_value'], 0, ',', '.') }}</p>
    </div>
    <div class="bunkue-card p-5">
        <p class="text-sm text-stone-500">Periode laporan</p>
        <p class="mt-3 text-sm font-semibold">{{ $summary['period']['from'] }} s.d. {{ $summary['period']['to'] }}</p>
    </div>
</div>
<div class="bunkue-card mt-6 p-6">
    <h2 class="text-base font-semibold text-stone-900">Produk terlaris</h2>
    <div class="mt-4 divide-y divide-stone-100">
        @forelse ($summary['top_products'] as $item)
        <div class="flex flex-wrap justify-between gap-2 py-3 text-sm"><span class="font-medium">{{ $item['product'] }}</span><span class="text-stone-600">{{ $item['units_sold'] }} terjual · Rp {{ number_format($item['revenue'], 0, ',', '.') }}</span></div>
        @empty
        <p class="py-4 text-sm text-stone-500">Belum ada data pada periode ini.</p>
        @endforelse
    </div>
</div>
@endsection