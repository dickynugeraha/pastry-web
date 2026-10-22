@extends('layouts.admin')

@section('title', 'Produk · Admin')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="bunkue-kicker">Katalog toko</p>
        <h1 class="mt-2 text-3xl text-stone-900">Produk</h1>
        <p class="mt-2 text-sm text-stone-600">Kelola harga, stok, dan foto produk.</p>
    </div>
    <a href="{{ route('admin.products.create') }}" class="bunkue-button-primary"><x-lucide-plus class="size-4" aria-hidden="true" /> Tambah produk</a>
</div>
<form method="GET" action="{{ route('admin.products.index') }}" class="mt-7 flex flex-wrap items-end gap-3">
    <label class="grid gap-2 text-xs font-medium text-stone-600">Cari produk
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama produk">
    </label>
    <button type="submit" class="bunkue-button-secondary">Cari</button>
</form>
<div class="bunkue-card mt-6 overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-stone-200 bg-stone-50 text-xs text-stone-500">
            <tr>
                <th class="px-5 py-4">Produk</th>
                <th class="px-5 py-4">Kategori</th>
                <th class="px-5 py-4">Harga</th>
                <th class="px-5 py-4">Stok</th>
                <th class="px-5 py-4">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
            @forelse ($products as $product)
            <tr>
                <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-lg bg-stone-100 text-stone-400">@if ($product->primary_image_url)<img src="{{ $product->primary_image_url }}" alt="" class="size-full object-cover">@else<x-lucide-image class="size-5" aria-hidden="true" />@endif</div>
                        <div>
                            <p class="font-semibold text-stone-900">{{ $product->name }}</p>
                            <p class="text-xs text-stone-500">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4 text-stone-600">{{ $product->category?->name ?? '—' }}</td>
                <td class="px-5 py-4">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                <td class="px-5 py-4">{{ $product->stock }}</td>
                <td class="px-5 py-4">
                    <div class="flex items-center gap-3"><a href="{{ route('admin.products.show', $product) }}" class="font-medium text-[#775744] hover:underline">Detail</a><a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-stone-600 hover:underline">Edit</a>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}">@csrf @method('DELETE')<button type="submit" onclick="return confirm('Hapus produk ini?')" class="font-medium text-red-700 hover:underline">Hapus</button></form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-5 py-12 text-center text-stone-500">Belum ada produk yang cocok.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $products->links() }}</div>
@endsection