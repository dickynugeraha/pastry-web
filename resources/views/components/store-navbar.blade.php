@php
$currentUser = auth()->user();
$cartItemCount = $currentUser?->cart?->items()->sum('qty') ?? 0;
$links = [
['label' => 'Beranda', 'href' => route('home'), 'active' => request()->routeIs('home')],
['label' => 'Produk', 'href' => route('products.index'), 'active' => request()->routeIs('products.*')],
];
@endphp

<header x-data="{ open: false }" @keydown.escape.window="open = false" class="sticky top-0 z-50 border-b border-stone-200 bg-[#faf8f5]/95 backdrop-blur">
    <div class="bunkue-container flex h-18 items-center gap-6">
        <a href="{{ route('home') }}" class="shrink-0 font-serif text-2xl tracking-tight text-stone-900" aria-label="Bunkue Pastry, beranda">Bunkue<span class="text-[#9a7359]">.</span></a>

        <nav class="hidden items-center gap-7 md:flex" aria-label="Navigasi utama">
            @foreach ($links as $link)
            <a href="{{ $link['href'] }}" @if ($link['active']) aria-current="page" @endif class="border-b-2 py-6 text-sm font-medium transition {{ $link['active'] ? 'border-stone-900 text-stone-900' : 'border-transparent text-stone-600 hover:text-stone-900' }}">{{ $link['label'] }}</a>
            @endforeach
            @auth
            <a href="{{ route('orders.index') }}" @if (request()->routeIs('orders.*')) aria-current="page" @endif class="border-b-2 py-6 text-sm font-medium transition {{ request()->routeIs('orders.*') ? 'border-stone-900 text-stone-900' : 'border-transparent text-stone-600 hover:text-stone-900' }}">Pesanan</a>
            @endauth
        </nav>

        <div class="ml-auto flex items-center gap-2 sm:gap-3">
            @guest
            <a href="{{ route('login') }}" class="hidden text-sm font-medium text-stone-700 hover:text-stone-900 sm:inline-flex">Masuk</a>
            <a href="{{ route('register') }}" class="bunkue-button-primary hidden sm:inline-flex">Daftar</a>
            @else
            @if ($currentUser->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="hidden text-sm font-medium text-stone-600 hover:text-stone-900 lg:inline-flex">Admin</a>
            @endif
            <a href="{{ route('profile.edit') }}" class="hidden max-w-36 truncate text-sm font-medium text-stone-700 hover:text-stone-900 sm:inline-flex" title="Profil {{ $currentUser->name }}">{{ $currentUser->name }}</a>
            <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                @csrf
                <button type="submit" class="text-sm font-medium text-stone-500 hover:text-stone-900">Keluar</button>
            </form>
            @endguest

            <a href="{{ route('cart.show') }}" class="relative inline-flex size-10 items-center justify-center rounded-xl border border-stone-200 bg-white text-stone-800 transition hover:bg-stone-100" aria-label="Keranjang{{ $cartItemCount > 0 ? ', '.$cartItemCount.' item' : '' }}">
                <x-lucide-shopping-bag class="size-5" aria-hidden="true" />
                @if ($cartItemCount > 0)
                <span class="absolute -right-1.5 -top-1.5 grid min-w-5 place-items-center rounded-full bg-stone-900 px-1 text-[10px] font-semibold leading-5 text-white">{{ $cartItemCount > 99 ? '99+' : $cartItemCount }}</span>
                @endif
            </a>
            <button type="button" class="inline-flex size-10 items-center justify-center rounded-xl border border-stone-200 bg-white text-stone-800 md:hidden" @click="open = !open" :aria-expanded="open.toString()" aria-controls="store-mobile-navigation" aria-label="Buka menu">
                <x-lucide-menu x-show="!open" class="size-5" aria-hidden="true" />
                <x-lucide-x x-show="open" x-cloak class="size-5" aria-hidden="true" />
            </button>
        </div>
    </div>

    <nav id="store-mobile-navigation" x-cloak x-show="open" x-transition class="border-t border-stone-200 bg-[#faf8f5] md:hidden" aria-label="Navigasi mobile">
        <div class="bunkue-container grid gap-1 py-4">
            @foreach ($links as $link)
            <a href="{{ $link['href'] }}" @if ($link['active']) aria-current="page" @endif class="rounded-xl px-4 py-3 text-sm font-medium {{ $link['active'] ? 'bg-white text-stone-900' : 'text-stone-600 hover:bg-white' }}">{{ $link['label'] }}</a>
            @endforeach
            @auth
            <a href="{{ route('orders.index') }}" class="rounded-xl px-4 py-3 text-sm font-medium text-stone-600 hover:bg-white">Pesanan saya</a>
            <a href="{{ route('profile.edit') }}" class="rounded-xl px-4 py-3 text-sm font-medium text-stone-600 hover:bg-white">Profil</a>
            @if ($currentUser->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="rounded-xl px-4 py-3 text-sm font-medium text-stone-600 hover:bg-white">Panel admin</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="border-t border-stone-200 pt-2">
                @csrf
                <button type="submit" class="w-full rounded-xl px-4 py-3 text-left text-sm font-medium text-stone-600 hover:bg-white">Keluar</button>
            </form>
            @else
            <div class="grid grid-cols-2 gap-2 border-t border-stone-200 pt-3">
                <a href="{{ route('login') }}" class="bunkue-button-secondary">Masuk</a>
                <a href="{{ route('register') }}" class="bunkue-button-primary">Daftar</a>
            </div>
            @endauth
        </div>
    </nav>
</header>