@extends('layouts.admin')

@section('title', 'Detail Produk')

@section('content')
<div class="max-w-4xl">
    <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-[#775744] hover:underline">
        <x-lucide-arrow-left class="size-4" aria-hidden="true" /> Produk
    </a>

    <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm text-stone-500">{{ $product->category?->name ?? 'Tanpa kategori' }}</p>
            <h1 class="mt-1 break-words text-2xl font-semibold text-stone-900 sm:text-3xl">{{ $product->name }}</h1>
            <p class="mt-1 break-all text-sm text-stone-500">/{{ $product->slug }}</p>
        </div>
        <a href="{{ route('admin.products.edit', $product) }}" class="bunkue-button-secondary shrink-0">Edit produk</a>
    </div>

    <section class="bunkue-card mt-6 p-5 sm:p-6" aria-labelledby="product-information-heading">
        <h2 id="product-information-heading" class="text-base font-semibold text-stone-900">Informasi produk</h2>
        <dl class="mt-4 grid gap-5 sm:grid-cols-3">
            <div>
                <dt class="text-sm text-stone-500">Harga</dt>
                <dd class="mt-1 text-lg font-semibold text-stone-900">Rp {{ number_format($product->price, 0, ',', '.') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-500">Stok</dt>
                <dd class="mt-1 text-lg font-semibold text-stone-900">{{ $product->stock }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-500">Status</dt>
                <dd class="mt-1 text-lg font-semibold text-stone-900">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</dd>
            </div>
        </dl>

        <div class="mt-5 border-t border-stone-200 pt-5">
            <h3 class="text-sm font-medium text-stone-900">Deskripsi</h3>
            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-stone-600">{{ $product->description ?: 'Tidak ada deskripsi.' }}</p>
        </div>
    </section>

    <section class="bunkue-card mt-5 p-5 sm:p-6" aria-labelledby="product-photos-heading">
        <h2 id="product-photos-heading" class="text-base font-semibold text-stone-900">Foto produk</h2>
        @if ($product->images->isNotEmpty())
        @php
        $galleryImages = $product->images->values()->map(fn (\App\Models\ProductImage $image, int $index): array => [
        'src' => $image->url,
        'alt' => $product->name.' - foto '.($index + 1),
        'badge' => $image->is_primary ? 'Foto utama' : null,
        ])->all();
        @endphp
        <x-ui.gallery :images="$galleryImages" title="Foto {{ $product->name }}" layout="wrap" thumbnail-class="size-28 sm:size-32" rounded="rounded-xl" class="mt-4" />
        @else
        <p class="mt-3 text-sm text-stone-500">Belum ada foto produk.</p>
        @endif
    </section>
</div>
@endsection