@extends('layouts.admin')

@section('title', 'Pesanan · Admin')

@section('content')
<div>
    <p class="bunkue-kicker">Operasional</p>
    <h1 class="mt-2 text-3xl text-stone-900">Pesanan</h1>
    <p class="mt-2 text-sm text-stone-600">Cari pesanan dan perbarui statusnya.</p>
</div>
<form method="GET" action="{{ route('admin.orders.index') }}" class="mt-7 flex flex-wrap items-end gap-3">
    <label class="grid gap-2 text-xs font-medium text-stone-600">Cari pesanan
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Kode, nama, atau email">
    </label>
    <label class="grid gap-2 text-xs font-medium text-stone-600">Status pesanan
        <select name="status">
            <option value="">Semua status</option>@foreach (['pending', 'processing', 'completed', 'cancelled'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '' )===$status)>{{ ucfirst($status) }}</option>@endforeach
        </select>
    </label>
    <label class="grid gap-2 text-xs font-medium text-stone-600">Pembayaran
        <select name="payment_status">
            <option value="">Semua status</option>@foreach (['unpaid', 'paid', 'failed', 'refunded'] as $status)<option value="{{ $status }}" @selected(($filters['payment_status'] ?? '' )===$status)>{{ ucfirst($status) }}</option>@endforeach
        </select>
    </label>
    <button type="submit" class="bunkue-button-primary">Terapkan filter</button>
</form>
<div class="bunkue-card mt-6 overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-stone-200 bg-stone-50 text-xs text-stone-500">
            <tr>
                <th class="px-5 py-4">Pesanan</th>
                <th class="px-5 py-4">Pelanggan</th>
                <th class="px-5 py-4">Status</th>
                <th class="px-5 py-4">Total</th>
                <th class="px-5 py-4">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
            @forelse ($orders as $order)
            <tr>
                <td class="px-5 py-4"><span class="font-semibold">{{ $order->order_code }}</span><span class="block text-xs text-stone-500">{{ $order->created_at->format('d M Y H:i') }}</span></td>
                <td class="px-5 py-4">{{ $order->user?->name }}<span class="block text-xs text-stone-500">{{ $order->user?->email }}</span></td>
                <td class="px-5 py-4">@include('admin.partials.order-status-badge', ['status' => $order->shipping_status])</td>
                <td class="px-5 py-4 font-semibold">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                <td class="px-5 py-4"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-[#775744] hover:underline">Kelola</a></td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-5 py-12 text-center text-stone-500">Belum ada pesanan yang cocok.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $orders->links() }}</div>
@endsection