@extends('layouts.store')

@section('title', 'Semua Produk · Bunkue Pastry')

@section('content')
<section class="bunkue-container py-12 md:py-16">
    <div class="max-w-2xl">
        <p class="bunkue-kicker">Katalog</p>
        <h1 class="bunkue-page-title mt-4 text-stone-900">Pilih pastry favorit Anda</h1>
        <p class="mt-4 text-sm leading-7 text-stone-600">Temukan kue dan pastry yang cocok untuk momen Anda. Gunakan pencarian atau kategori untuk mempersempit pilihan.</p>
    </div>

    <form method="GET" action="{{ route('products.index') }}" class="bunkue-card mt-9 grid items-end gap-4 p-4 md:grid-cols-[minmax(0,1fr)_13rem_auto] md:p-5">
        <div>
            <label for="search" class="mb-2 block text-xs font-semibold text-stone-700">Cari produk</label>
            <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama produk" class="w-full">
        </div>
        <div>
            <label for="category_id" class="mb-2 block text-xs font-semibold text-stone-700">Kategori</label>
            <select id="category_id" name="category_id" class="w-full">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '' )==$category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bunkue-button-primary w-full md:w-auto">Tampilkan produk</button>
        <label class="flex items-center gap-2 text-sm text-stone-600 md:col-span-3">
            <input type="checkbox" name="available_only" value="1" @checked($filters['available_only'] ?? false) class="rounded border-stone-300">
            Hanya yang tersedia
        </label>
    </form>

    <div class="mt-10 flex flex-wrap items-end justify-between gap-3 border-b border-stone-200 pb-4">
        <p class="text-sm font-medium text-stone-700">{{ $products->total() }} produk ditemukan</p>
        @if (request()->filled('search') || request()->filled('category_id') || request()->boolean('available_only'))
        <a href="{{ route('products.index') }}" class="text-sm font-medium text-[#775744] hover:underline">Hapus filter</a>
        @endif
    </div>

    @if ($products->count() > 0)
    <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($products as $product)
        <x-store.product-card :product="$product" :cart-quantity="$cartQuantities[$product->id] ?? 0" />
        @endforeach
    </div>
    <div class="mt-10">{{ $products->links() }}</div>
    @else
    <div class="bunkue-card mt-6 px-6 py-16 text-center">
        <x-lucide-search-x class="mx-auto size-10 text-stone-400" aria-hidden="true" />
        <h2 class="mt-5 font-serif text-2xl text-stone-900">Produk belum ditemukan</h2>
        <p class="mt-2 text-sm text-stone-600">Coba ubah kata kunci atau pilih kategori lain.</p>
        <a href="{{ route('products.index') }}" class="bunkue-button-secondary mt-6">Lihat semua produk</a>
    </div>
    @endif
</section>
@endsection
