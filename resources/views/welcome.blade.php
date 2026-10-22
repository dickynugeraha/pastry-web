@extends('layouts.store')

@section('title', 'Bunkue Pastry · Kue untuk setiap momen')

@section('content')
<section class="border-b border-stone-200 bg-[#f5f0e9]">
    <div class="bunkue-container grid items-center gap-10 py-14 md:grid-cols-2 md:gap-16 md:py-20">
        <div class="max-w-xl">
            <p class="bunkue-kicker">Dibuat dengan perhatian</p>
            <h1 class="bunkue-page-title mt-5 text-stone-900">Pastry yang membuat momen terasa lebih hangat.</h1>
            <p class="mt-6 max-w-lg text-base leading-7 text-stone-600">Pilih kue favorit, tambahkan ke keranjang, lalu tentukan cara menerima pesanan Anda. Semuanya bisa dilakukan dengan mudah di sini.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('products.index') }}" class="bunkue-button-primary">
                    Belanja sekarang
                    <x-lucide-arrow-right class="size-4" aria-hidden="true" />
                </a>
                <a href="#cara-pesan" class="bunkue-button-secondary">Lihat cara pesan</a>
            </div>
        </div>
        <div class="overflow-hidden rounded-[1.5rem] bg-stone-200">
            <img src="https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=1100&q=85" alt="Kue lapis dengan krim dan buah" class="aspect-[5/4] w-full object-cover" fetchpriority="high">
        </div>
    </div>
</section>

<section id="katalog" class="bunkue-container py-16 md:py-22">
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div>
            <p class="bunkue-kicker">Pilihan untuk Anda</p>
            <h2 class="bunkue-section-title mt-3 text-stone-900">Temukan rasa favorit Anda</h2>
            <p class="mt-3 max-w-xl text-sm leading-6 text-stone-600">Jelajahi produk yang tersedia dan lihat detailnya sebelum memesan.</p>
        </div>
        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-stone-800 hover:text-[#775744]">
            Lihat semua produk
            <x-lucide-arrow-up-right class="size-4" aria-hidden="true" />
        </a>
    </div>

    @if ($featuredProducts->isNotEmpty())
    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($featuredProducts as $product)
        <x-store.product-card :product="$product" :cart-quantity="$cartQuantities[$product->id] ?? 0" />
        @endforeach
    </div>
    @else
    <div class="bunkue-card mt-8 p-10 text-center">
        <h3 class="font-serif text-2xl text-stone-900">Produk segera hadir</h3>
        <p class="mt-2 text-sm text-stone-600">Koleksi kami sedang disiapkan. Kunjungi kembali halaman produk nanti.</p>
        <a href="{{ route('products.index') }}" class="bunkue-button-secondary mt-5">Buka katalog</a>
    </div>
    @endif
</section>

<section id="cara-pesan" class="border-y border-stone-200 bg-white py-16 md:py-20">
    <div class="bunkue-container">
        <div class="max-w-xl">
            <p class="bunkue-kicker">Cara pesan</p>
            <h2 class="bunkue-section-title mt-3 text-stone-900">Dari pilihan hingga pesanan</h2>
            <p class="mt-3 text-sm leading-6 text-stone-600">Tiga langkah sederhana untuk menikmati pastry pilihan Anda.</p>
        </div>
        <div class="mt-9 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-stone-200 p-6">
                <span class="text-xs font-semibold text-[#775744]">01</span>
                <x-lucide-search class="mt-6 size-6 text-stone-700" aria-hidden="true" />
                <h3 class="mt-4 text-base font-semibold text-stone-900">Pilih produk</h3>
                <p class="mt-2 text-sm leading-6 text-stone-600">Cari dari katalog, lalu periksa harga dan ketersediaan.</p>
            </div>
            <div class="rounded-2xl border border-stone-200 p-6">
                <span class="text-xs font-semibold text-[#775744]">02</span>
                <x-lucide-shopping-bag class="mt-6 size-6 text-stone-700" aria-hidden="true" />
                <h3 class="mt-4 text-base font-semibold text-stone-900">Periksa keranjang</h3>
                <p class="mt-2 text-sm leading-6 text-stone-600">Atur jumlah produk dan lihat total pesanan sebelum checkout.</p>
            </div>
            <div class="rounded-2xl border border-stone-200 p-6">
                <span class="text-xs font-semibold text-[#775744]">03</span>
                <x-lucide-package-check class="mt-6 size-6 text-stone-700" aria-hidden="true" />
                <h3 class="mt-4 text-base font-semibold text-stone-900">Buat pesanan</h3>
                <p class="mt-2 text-sm leading-6 text-stone-600">Pilih ambil sendiri atau diantar, lalu pantau status di akun Anda.</p>
            </div>
        </div>
    </div>
</section>

<section class="bunkue-container py-16 md:py-20">
    <div class="flex flex-col items-start justify-between gap-6 rounded-2xl bg-[#eae1d7] p-8 sm:p-10 md:flex-row md:items-center">
        <div>
            <h2 class="bunkue-section-title text-stone-900">Ada yang ingin dirayakan?</h2>
            <p class="mt-2 text-sm leading-6 text-stone-700">Mulai dari memilih pastry yang tepat untuk momen Anda.</p>
        </div>
        <a href="{{ route('products.index') }}" class="bunkue-button-primary shrink-0">Jelajahi produk</a>
    </div>
</section>
@endsection
