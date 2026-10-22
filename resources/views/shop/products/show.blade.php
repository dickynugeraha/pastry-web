@extends('layouts.store')

@section('title', $product->name.' · Bunkue Pastry')

@section('content')
<section class="bunkue-container py-10 md:py-14">
    <nav aria-label="Breadcrumb" class="mb-7 flex items-center gap-2 text-sm text-stone-500">
        <a href="{{ route('home') }}" class="hover:text-stone-900">Beranda</a>
        <x-lucide-chevron-right class="size-3.5" aria-hidden="true" />
        <a href="{{ route('products.index') }}" class="hover:text-stone-900">Produk</a>
        <x-lucide-chevron-right class="size-3.5" aria-hidden="true" />
        <span class="truncate text-stone-800" aria-current="page">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-8 lg:grid-cols-2 lg:gap-14">
        <div class="overflow-hidden rounded-2xl bg-[#eee7df]">
            @if ($product->primary_image_url)
            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover">
            @else
            <span class="grid aspect-square place-items-center text-stone-400"><x-lucide-image class="size-20" aria-hidden="true" /></span>
            @endif
        </div>
        <div class="self-center">
            <p class="bunkue-kicker">{{ $product->category?->name ?? 'Pastry' }}</p>
            <h1 class="bunkue-page-title mt-3 text-stone-900">{{ $product->name }}</h1>
            <p class="mt-5 text-2xl font-semibold text-stone-900">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
            <p class="mt-6 max-w-xl whitespace-pre-line text-sm leading-7 text-stone-600">{{ $product->description ?: 'Dibuat dengan bahan pilihan untuk melengkapi momen Anda.' }}</p>

            <div class="mt-8 border-y border-stone-200 py-5">
                <p class="flex items-center gap-2 text-sm font-medium {{ $product->stock > 0 ? 'text-stone-700' : 'text-red-700' }}">
                    <span class="size-2 rounded-full {{ $product->stock > 0 ? 'bg-emerald-600' : 'bg-red-600' }}"></span>
                    {{ $product->stock > 0 ? $product->stock.' tersedia' : 'Stok sedang habis' }}
                </p>
            </div>

            @if ($errors->any())
            <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            @if ($product->stock > 0)
            @auth
            <div class="mt-6">
                <x-store.cart-quantity-dialog :product="$product" :cart-quantity="$cartQuantities[$product->id] ?? 0">
                    <x-slot:trigger>
                        <button type="button" class="bunkue-button-primary">
                            <x-lucide-shopping-bag class="size-4" aria-hidden="true" />
                            Keranjang
                        </button>
                    </x-slot:trigger>
                </x-store.cart-quantity-dialog>
            </div>
            @else
            <div class="mt-6">
                <a href="{{ route('login') }}" class="bunkue-button-primary">Masuk untuk membeli</a>
                <p class="mt-3 text-xs text-stone-500">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-[#775744] hover:underline">Daftar di sini</a>.</p>
            </div>
            @endauth
            @endif
            <p class="mt-5 text-xs leading-5 text-stone-500">Periksa kembali pesanan di keranjang sebelum checkout.</p>
        </div>
    </div>
</section>
@endsection
