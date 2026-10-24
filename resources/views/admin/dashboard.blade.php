@extends('layouts.admin')

@section('title', 'Dashboard · Admin')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="bunkue-kicker">Ringkasan toko</p>
            <h1 class="mt-2 text-3xl text-stone-900">Dashboard</h1>
            <p class="mt-2 text-sm text-stone-600">Pantau pesanan dan penjualan sesuai periode.</p>
        </div>
        @include('admin.partials.report-filter', ['action' => route('admin.dashboard')])
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="bunkue-card p-5"><p class="text-sm text-stone-500">Total pesanan</p><p class="mt-3 text-2xl font-semibold text-stone-900">{{ number_format($summary['total_orders'] ?? 0) }}</p></div>
        <div class="bunkue-card p-5"><p class="text-sm text-stone-500">Pendapatan</p><p class="mt-3 text-2xl font-semibold text-stone-900">Rp {{ number_format($summary['total_revenue'] ?? 0, 0, ',', '.') }}</p></div>
        <div class="bunkue-card p-5"><p class="text-sm text-stone-500">Rata-rata pesanan</p><p class="mt-3 text-2xl font-semibold text-stone-900">Rp {{ number_format($summary['average_order_value'] ?? 0, 0, ',', '.') }}</p></div>
        <div class="bunkue-card p-5"><p class="text-sm text-stone-500">Periode</p><p class="mt-3 text-base font-semibold text-stone-900">{{ $summary['period']['from'] ?? '-' }} <span class="font-normal text-stone-400">sampai</span> {{ $summary['period']['to'] ?? '-' }}</p></div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="bunkue-card p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-stone-900">Aktivitas pesanan</h2>
                    <p class="mt-1 text-xs text-stone-500">Grafik penjualan dari pembayaran yang sudah sah</p>
                </div>
                <a href="{{ route('admin.reports.orders', request()->query()) }}" class="text-sm font-medium text-[#775744] hover:underline">Lihat laporan</a>
            </div>
            @if ($summary['total_orders'] > 0)
                @php
                    $chart = $summary['sales_chart'];
                    $maximumRevenue = max(1, max(array_column($chart, 'revenue')));
                    $labelInterval = max(1, (int) ceil(count($chart) / 7));
                @endphp
                <div class="mt-6 flex items-center justify-between gap-4 text-xs text-stone-500">
                    <span>Total periode</span>
                    <strong class="text-lg font-semibold text-stone-900">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</strong>
                </div>
                <div class="mt-4 overflow-x-auto" role="img" aria-label="Grafik pendapatan penjualan sesuai periode terpilih">
                    <div class="flex min-w-full items-end gap-1.5 border-b border-stone-200" style="width: max(100%, {{ count($chart) * 28 }}px)">
                        @foreach ($chart as $index => $point)
                            <div class="flex min-w-0 flex-1 flex-col items-center" title="{{ $point['label'] }}: Rp {{ number_format($point['revenue'], 0, ',', '.') }} · {{ $point['orders'] }} pesanan">
                                <div class="flex h-40 w-full items-end justify-center">
                                    <div class="w-full max-w-9 rounded-t-md {{ $point['revenue'] > 0 ? 'bg-[#9a7359]' : 'bg-stone-200' }}" style="height: {{ $point['revenue'] > 0 ? max(8, round($point['revenue'] / $maximumRevenue * 100)) : 3 }}%"></div>
                                </div>
                                <span class="h-6 w-full truncate pt-1 text-center text-[10px] text-stone-500">{{ $index % $labelInterval === 0 || $index === count($chart) - 1 ? $point['label'] : '' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="mt-6 flex h-48 items-center justify-center rounded-xl bg-stone-50 text-sm text-stone-500">Belum ada aktivitas pada periode ini.</div>
            @endif
        </div>
        <div class="bunkue-card p-6">
            <h2 class="text-base font-semibold text-stone-900">Status pesanan</h2>
            <div class="mt-5 grid gap-4">
                @forelse ($summary['status_counts'] ?? [] as $status => $count)
                    <div>
                        <div class="flex justify-between text-sm"><span class="text-stone-600">{{ ucfirst($status) }}</span><span class="font-semibold text-stone-900">{{ $count }}</span></div>
                        <div class="mt-2 h-1.5 rounded-full bg-stone-100"><div class="h-1.5 rounded-full bg-[#b9a18d]" style="width: {{ min(100, $count / max(1, $summary['total_orders'] ?? 1) * 100) }}%"></div></div>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Belum ada pesanan.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bunkue-card mt-6 p-6">
        <h2 class="text-base font-semibold text-stone-900">Produk terlaris</h2>
        <div class="mt-4 divide-y divide-stone-100">
            @forelse ($summary['top_products'] ?? [] as $item)
                <div class="flex flex-wrap justify-between gap-2 py-3 text-sm"><span class="font-medium text-stone-900">{{ $item['product'] }}</span><span class="text-stone-600">{{ $item['units_sold'] }} terjual · Rp {{ number_format($item['revenue'], 0, ',', '.') }}</span></div>
            @empty
                <p class="py-4 text-sm text-stone-500">Belum ada data produk.</p>
            @endforelse
        </div>
    </div>
@endsection
