<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bunkue Pastry — Artisanal Homemade Cakes</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-stone-50 text-stone-800 font-sans antialiased selection:bg-stone-900 selection:text-white">

    {{-- ============ NAVBAR ============ --}}
    <div class="sticky top-0 z-50 bg-stone-50/80 backdrop-blur-lg border-b border-stone-200/60">
        <x-ui.navigation-menu class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between">
            {{-- Logo --}}
            <a href="/" class="text-xl font-serif font-bold tracking-widest text-stone-900 uppercase">
                Bunkue<span class="italic font-light">Pastry</span>
            </a>

            {{-- Menu --}}
            <x-ui.navigation-menu-list class="hidden md:flex items-center gap-10 text-sm font-medium tracking-wide text-stone-500">
                <x-ui.navigation-menu-item>
                    <x-ui.navigation-menu-link href="/" class="hover:text-stone-900 transition-colors">
                        Home
                    </x-ui.navigation-menu-link>
                </x-ui.navigation-menu-item>
                <x-ui.navigation-menu-item>
                    <x-ui.navigation-menu-link href="#katalog" class="hover:text-stone-900 transition-colors">
                        Katalog
                    </x-ui.navigation-menu-link>
                </x-ui.navigation-menu-item>
                <x-ui.navigation-menu-item>
                    <x-ui.navigation-menu-link href="#keunggulan" class="hover:text-stone-900 transition-colors">
                        Keunggulan
                    </x-ui.navigation-menu-link>
                </x-ui.navigation-menu-item>
                <x-ui.navigation-menu-item>
                    <x-ui.navigation-menu-link href="#kontak" class="hover:text-stone-900 transition-colors">
                        Kontak
                    </x-ui.navigation-menu-link>
                </x-ui.navigation-menu-item>
            </x-ui.navigation-menu-list>

            {{-- CTA --}}
            <a href="#katalog" class="text-xs font-semibold tracking-widest uppercase bg-stone-900 text-white px-6 py-3 rounded-full hover:bg-stone-700 transition-all duration-300">
                Pesan
            </a>
        </x-ui.navigation-menu>
    </div>

    {{-- ============ HERO ============ --}}
    <section class="max-w-6xl mx-auto px-6 py-24 md:py-32 grid md:grid-cols-2 gap-16 items-center">
        <div class="order-2 md:order-1">
            <p class="text-xs font-semibold tracking-[0.2em] uppercase text-stone-400 mb-6">
                Artisanal • Fresh Setiap Hari
            </p>
            <h1 class="text-4xl md:text-6xl font-serif leading-[1.15] text-stone-900 mb-8">
                Kelezatan yang <br>
                Dibuat dengan <span class="italic text-stone-500">Cinta</span>
            </h1>
            <p class="text-stone-500 leading-relaxed mb-10 max-w-md text-sm md:text-base">
                Bunkue Pastry menghadirkan kue premium dengan bahan pilihan, tanpa pengawet, dan dibuat dengan teliti di dapur sendiri. Cocok untuk hadiah, ulang tahun, maupun cemilan keluarga.
            </p>
            <div class="flex flex-wrap gap-4">
                <a href="#katalog" class="bg-stone-900 text-white px-8 py-4 rounded-full text-sm font-medium tracking-wide hover:bg-stone-700 transition-all duration-300">
                    Lihat Katalog
                </a>
                <a href="#kontak" class="border border-stone-300 text-stone-900 px-8 py-4 rounded-full text-sm font-medium tracking-wide hover:bg-stone-100 transition-all duration-300">
                    Hubungi Kami
                </a>
            </div>
        </div>

        <div class="order-1 md:order-2 relative">
            <div class="aspect-[4/5] overflow-hidden rounded-[2rem]">
                <img
                    src="https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=800&q=80"
                    alt="Kue Bunkue Pastry"
                    class="object-cover w-full h-full hover:scale-105 transition-transform duration-700 ease-out">
            </div>
            {{-- Aksen dekoratif minimalis --}}
            <div class="absolute -bottom-6 -left-6 -z-10 w-48 h-48 bg-stone-200 rounded-full blur-3xl opacity-50"></div>
        </div>
    </section>

    {{-- ============ KATALOG SINGKAT ============ --}}
    <section id="katalog" class="py-24 bg-white border-y border-stone-100">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center max-w-xl mx-auto mb-20">
                <h2 class="text-3xl md:text-4xl font-serif text-stone-900">
                    Koleksi Pilihan
                </h2>
                <p class="mt-4 text-stone-500 text-sm leading-relaxed">
                    Beberapa menu best seller yang paling sering dipesan pelanggan Bunkue Pastry.
                </p>
            </div>

            @php
            $produk = [
            ['nama' => 'Choco Fudge Cake', 'harga' => 'Rp 120.000', 'img' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?w=600&q=80', 'tag' => 'Best Seller'],
            ['nama' => 'Strawberry Tart', 'harga' => 'Rp 95.000', 'img' => 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=600&q=80', 'tag' => 'Favorit'],
            ['nama' => 'Red Velvet Slice', 'harga' => 'Rp 45.000', 'img' => 'https://images.unsplash.com/photo-1586985289906-406988974504?w=600&q=80', 'tag' => 'Baru'],
            ['nama' => 'Cheese Roll', 'harga' => 'Rp 60.000', 'img' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600&q=80', 'tag' => 'Best Seller'],
            ['nama' => 'Donat Gula', 'harga' => 'Rp 25.000', 'img' => 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=600&q=80', 'tag' => 'Cemilan'],
            ['nama' => 'Cupcake Vanilla', 'harga' => 'Rp 30.000', 'img' => 'https://images.unsplash.com/photo-1614707267537-b85aaf00c4b7?w=600&q=80', 'tag' => 'Manis'],
            ];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-16">
                @foreach ($produk as $item)
                <div class="group cursor-pointer">
                    {{-- Image --}}
                    <div class="relative overflow-hidden rounded-2xl aspect-[4/5] mb-5 bg-stone-100">
                        <img src="{{ $item['img'] }}" alt="{{ $item['nama'] }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out">
                        {{-- Tag --}}
                        <span class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm text-stone-800 text-[10px] font-bold tracking-widest uppercase px-3 py-1.5 rounded-full">
                            {{ $item['tag'] }}
                        </span>
                    </div>

                    {{-- Info --}}
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-base font-serif text-stone-900 group-hover:text-stone-600 transition-colors">
                                {{ $item['nama'] }}
                            </h3>
                            <p class="text-xs text-stone-400 mt-1">Homemade • Fresh</p>
                        </div>
                        <span class="text-sm font-semibold text-stone-900">{{ $item['harga'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-20 text-center">
                <a href="/products" class="inline-flex items-center gap-2 text-sm font-medium tracking-wide text-stone-900 border-b border-stone-300 pb-1 hover:border-stone-900 transition-colors">
                    Lihat Semua Produk
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

    {{-- ============ KEUNGGULAN ============ --}}
    <section id="keunggulan" class="py-24 bg-stone-50">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center max-w-xl mx-auto mb-20">
                <h2 class="text-3xl md:text-4xl font-serif text-stone-900">
                    Standar Terbaik
                </h2>
                <p class="mt-4 text-stone-500 text-sm leading-relaxed">
                    Kami percaya bahwa kue yang baik dimulai dari bahan yang baik.
                </p>
            </div>

            @php
            $fitur = [
            ['icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'judul' => 'Bahan Premium', 'desc' => 'Menggunakan bahan pilihan berkualitas tanpa bahan pengawet.'],
            ['icon' => 'M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18zm-3-9v-2a2 2 0 00-2-2H8a2 2 0 00-2 2v2h12z', 'judul' => 'Homemade', 'desc' => 'Dibuat langsung di dapur sendiri, fresh setiap hari.'],
            ['icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'judul' => 'Custom Design', 'desc' => 'Bisa request bentuk, rasa, dan tulisan sesuai keinginan.'],
            ['icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', 'judul' => 'Pengiriman Aman', 'desc' => 'Dikemas rapi dan aman sampai ke tangan Anda.'],
            ];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
                @foreach ($fitur as $f)
                <div class="text-center">
                    <div class="w-12 h-12 mx-auto mb-6 flex items-center justify-center text-stone-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="{{ $f['icon'] }}" />
                        </svg>
                    </div>
                    <h3 class="text-base font-serif text-stone-900 mb-2">{{ $f['judul'] }}</h3>
                    <p class="text-xs text-stone-500 leading-relaxed px-4">{{ $f['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ CTA / KONTAK ============ --}}
    <section id="kontak" class="py-32 bg-stone-900 text-stone-300">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-5xl font-serif text-white mb-6">
                Siap Pesan Kue Spesial?
            </h2>
            <p class="text-stone-400 text-sm md:text-base max-w-xl mx-auto leading-relaxed mb-12">
                Hubungi kami via WhatsApp untuk konsultasi custom cake, harga, dan jadwal pengiriman.
            </p>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="https://wa.me/6281234567890" class="bg-white text-stone-900 px-8 py-4 rounded-full text-sm font-semibold tracking-wide hover:bg-stone-200 transition-colors duration-300">
                    WhatsApp
                </a>
                <a href="mailto:hello@bunkue.id" class="border border-stone-700 text-white px-8 py-4 rounded-full text-sm font-semibold tracking-wide hover:bg-stone-800 transition-colors duration-300">
                    Email
                </a>
            </div>

            <div class="mt-24 grid grid-cols-1 sm:grid-cols-3 gap-10 text-left border-t border-stone-800 pt-12">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.2em] text-stone-500 mb-2">Alamat</p>
                    <p class="text-sm font-light text-stone-300">Jl. Manis No. 10, Bandung</p>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-[0.2em] text-stone-500 mb-2">Jam Buka</p>
                    <p class="text-sm font-light text-stone-300">Setiap Hari • 08.00 – 20.00</p>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-[0.2em] text-stone-500 mb-2">Telepon</p>
                    <p class="text-sm font-light text-stone-300">+62 812-3456-7890</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FOOTER ============ --}}
    <footer class="bg-stone-900 border-t border-stone-800">
        <div class="max-w-6xl mx-auto px-6 py-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-xs text-stone-600 tracking-wider">
                &copy; {{ date('Y') }} BUNKUE PASTRY
            </p>
            <div class="flex gap-6 text-xs tracking-wider text-stone-500">
                <a href="#" class="hover:text-white transition-colors">INSTAGRAM</a>
                <a href="#" class="hover:text-white transition-colors">TIKTOK</a>
                <a href="#" class="hover:text-white transition-colors">SHOPEE</a>
            </div>
        </div>
    </footer>

</body>

</html>