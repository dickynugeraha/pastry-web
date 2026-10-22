@extends('layouts.store')

@section('title', 'Profil Saya · Bunkue Pastry')

@section('content')
@php
$isAdmin = $user->isAdmin();
$userInitials = \Illuminate\Support\Str::of($user->name)
->explode(' ')
->filter()
->take(2)
->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
->implode('');
@endphp

<section class="mx-auto max-w-7xl px-5 py-12 lg:px-8 lg:py-16">
    <div class="flex flex-col justify-between gap-6 border-b border-stone-200 pb-8 sm:flex-row sm:items-end">
        <div>
            <p class="bunkue-kicker">Akun Anda</p>
            <h1 class="bunkue-page-title mt-3 text-stone-900">Profil saya</h1>
            <p class="mt-3 max-w-xl text-sm leading-6 text-stone-500">Kelola informasi pribadi dan keamanan akun Anda dari satu tempat.</p>
        </div>
        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-stone-600 transition hover:text-stone-900">
            <x-lucide-arrow-left class="size-4" aria-hidden="true" />
            Kembali ke katalog
        </a>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)] lg:items-start">
        <aside class="bunkue-card overflow-hidden">
            <div class="bg-[#eee7df] px-6 py-7 text-stone-900">
                <div class="grid size-16 place-items-center rounded-2xl bg-white text-xl font-semibold text-[#775744]">{{ $userInitials }}</div>
                <h2 class="mt-5 truncate font-serif text-2xl">{{ $user->name }}</h2>
                <p class="mt-1 truncate text-sm text-stone-600">{{ $user->email }}</p>
                <span class="mt-5 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-stone-700">
                    <span class="size-1.5 rounded-full {{ $isAdmin ? 'bg-amber-400' : 'bg-emerald-400' }}"></span>
                    {{ $isAdmin ? 'Administrator' : 'Customer' }}
                </span>
            </div>

            <div class="space-y-2 p-4">
                <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400">Akses cepat</p>
                @if ($isAdmin)
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-xl bg-stone-100 px-3 py-3 text-sm font-semibold text-stone-800 transition hover:bg-stone-200">
                    <x-lucide-layout-dashboard class="size-4 text-amber-700" aria-hidden="true" />
                    Panel admin
                </a>
                <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm text-stone-600 transition hover:bg-stone-100 hover:text-stone-900">
                    <x-lucide-receipt-text class="size-4" aria-hidden="true" />
                    Kelola pesanan
                </a>
                @else
                <a href="{{ route('orders.index') }}" class="flex items-center gap-3 rounded-xl bg-stone-100 px-3 py-3 text-sm font-semibold text-stone-800 transition hover:bg-stone-200">
                    <x-lucide-receipt-text class="size-4 text-amber-700" aria-hidden="true" />
                    Pesanan saya
                </a>
                <a href="{{ route('cart.show') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm text-stone-600 transition hover:bg-stone-100 hover:text-stone-900">
                    <x-lucide-shopping-bag class="size-4" aria-hidden="true" />
                    Keranjang
                </a>
                @endif
            </div>
        </aside>

        <div class="min-w-0 space-y-6">
            <article class="bunkue-card p-6 sm:p-8">
                @include('profile.partials.update-profile-information-form')
            </article>

            <article class="bunkue-card p-6 sm:p-8">
                @include('profile.partials.update-password-form')
            </article>

            <article class="bunkue-card p-6 sm:p-8">
                @include('profile.partials.delete-user-form')
            </article>
        </div>
    </div>
</section>
@endsection