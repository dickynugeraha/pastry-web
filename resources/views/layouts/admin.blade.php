<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin · Bunkue Pastry')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bunkue-admin min-h-screen antialiased" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div x-cloak x-show="open" class="fixed inset-0 z-40 bg-stone-900/30 lg:hidden" @click="open = false" aria-hidden="true"></div>
    <div class="min-h-screen lg:flex">
        <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-stone-200 bg-white px-4 py-6 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0" :class="{ 'translate-x-0': open }">
            <div class="flex items-center justify-between px-3">
                <a href="{{ route('admin.dashboard') }}" class="font-serif text-2xl text-stone-900">Bunkue<span class="text-[#9a7359]">.</span></a>
                <button type="button" class="rounded-lg p-2 text-stone-600 lg:hidden" @click="open = false" aria-label="Tutup menu"><x-lucide-x class="size-5" /></button>
            </div>
            <p class="mt-2 px-3 text-xs font-medium text-stone-500">Panel admin</p>
            <nav class="mt-9 grid gap-1" aria-label="Navigasi admin">
                @foreach ([
                ['admin.dashboard', 'Dashboard'],
                ['admin.orders.index', 'Pesanan'],
                ['admin.products.index', 'Produk'],
                ['admin.users.index', 'Pengguna'],
                ['admin.reports.orders', 'Laporan'],
                ] as [$routeName, $label])
                @php
                $active = $routeName === 'admin.dashboard'
                ? request()->routeIs($routeName)
                : request()->routeIs(str_replace('.index', '.*', $routeName));
                @endphp
                <a href="{{ route($routeName) }}" @if ($active) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-[#f1eae3] text-[#664b3b]' : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900' }}">
                    @switch($routeName)
                    @case('admin.dashboard') <x-lucide-layout-dashboard class="size-4" aria-hidden="true" /> @break
                    @case('admin.orders.index') <x-lucide-receipt-text class="size-4" aria-hidden="true" /> @break
                    @case('admin.products.index') <x-lucide-package class="size-4" aria-hidden="true" /> @break
                    @case('admin.users.index') <x-lucide-users-round class="size-4" aria-hidden="true" /> @break
                    @case('admin.reports.orders') <x-lucide-chart-no-axes-column class="size-4" aria-hidden="true" /> @break
                    @endswitch
                    {{ $label }}
                </a>
                @endforeach
            </nav>
            <div class="mt-auto border-t border-stone-200 px-3 pt-5">
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-stone-600 hover:text-stone-900"><x-lucide-arrow-left class="size-4" aria-hidden="true" /> Lihat toko</a>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-30 flex min-h-18 items-center gap-4 border-b border-stone-200 bg-[#faf8f5]/95 px-5 backdrop-blur lg:px-8">
                <button type="button" class="rounded-lg p-2 text-stone-700 lg:hidden" @click="open = true" aria-label="Buka menu admin"><x-lucide-menu class="size-5" /></button>
                <p class="text-sm text-stone-500">Kelola toko</p>
                <div class="ml-auto flex items-center gap-4">
                    <a href="{{ route('profile.edit') }}" class="hidden text-sm font-medium text-stone-700 hover:text-stone-900 sm:inline">{{ auth()->user()->name }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-stone-600 hover:text-stone-900">Keluar</button>
                    </form>
                </div>
            </header>
            <x-toast-stack />
            <main class="mx-auto max-w-7xl p-5 pb-16 lg:p-8">
                @if ($errors->any())
                <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>

</html>