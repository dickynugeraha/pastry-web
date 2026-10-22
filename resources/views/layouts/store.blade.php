<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Bunkue Pastry menghadirkan pastry dan kue pilihan untuk momen sehari-hari.">
    <title>@yield('title', 'Bunkue Pastry')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bunkue-store flex min-h-screen flex-col antialiased">
    <x-store-navbar />
    <x-toast-stack />

    <main class="flex-1">@yield('content')</main>

    <footer class="border-t border-stone-200 bg-white">
        <div class="bunkue-container grid gap-10 py-12 sm:grid-cols-[1fr_auto_auto]">
            <div class="max-w-sm">
                <a href="{{ route('home') }}" class="font-serif text-2xl text-stone-900">Bunkue Pastry</a>
                <p class="mt-3 text-sm leading-6 text-stone-500">Pastry pilihan untuk melengkapi momen kecil bersama orang terdekat.</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-stone-400">Belanja</p>
                <div class="mt-4 grid gap-3 text-sm text-stone-600">
                    <a href="{{ route('products.index') }}" class="hover:text-stone-900">Semua produk</a>
                    <a href="{{ route('home') }}#cara-pesan" class="hover:text-stone-900">Cara pesan</a>
                    @auth
                    <a href="{{ route('cart.show') }}" class="hover:text-stone-900">Keranjang</a>
                    @endauth
                </div>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-stone-400">Akun</p>
                <div class="mt-4 grid gap-3 text-sm text-stone-600">
                    @auth
                    <a href="{{ route('orders.index') }}" class="hover:text-stone-900">Pesanan saya</a>
                    <a href="{{ route('profile.edit') }}" class="hover:text-stone-900">Profil</a>
                    @else
                    <a href="{{ route('login') }}" class="hover:text-stone-900">Masuk</a>
                    <a href="{{ route('register') }}" class="hover:text-stone-900">Daftar</a>
                    @endauth
                </div>
            </div>
        </div>
        <div class="border-t border-stone-100 py-5 text-center text-xs text-stone-400">© {{ date('Y') }} Bunkue Pastry</div>
    </footer>
    @stack('scripts')
</body>

</html>