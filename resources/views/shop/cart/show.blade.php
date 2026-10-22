@extends('layouts.store')

@section('title', 'Keranjang · Bunkue Pastry')

@section('content')
<section class="bunkue-container py-10 md:py-14">
    <nav aria-label="Tahapan belanja" class="flex items-center gap-2 text-xs font-medium text-stone-500">
        <span class="text-stone-900" aria-current="step">Keranjang</span>
        <x-lucide-chevron-right class="size-3.5" aria-hidden="true" />
        <span>Checkout</span>
        <x-lucide-chevron-right class="size-3.5" aria-hidden="true" />
        <span>Pesanan</span>
    </nav>
    <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="bunkue-kicker">Belanja Anda</p>
            <h1 class="bunkue-page-title mt-3">Keranjang</h1>
            <p class="mt-3 text-sm text-stone-600">Periksa produk dan jumlahnya sebelum melanjutkan.</p>
        </div>
        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-stone-700 hover:text-stone-900">
            <x-lucide-arrow-left class="size-4" aria-hidden="true" />
            Lanjut belanja
        </a>
    </div>

    @if ($errors->any())
    <div role="alert" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    @if ($cart->items->isEmpty())
    <div class="bunkue-card mt-8 px-6 py-16 text-center">
        <x-lucide-shopping-bag class="mx-auto size-10 text-stone-400" aria-hidden="true" />
        <h2 class="mt-5 font-serif text-2xl text-stone-900">Keranjang masih kosong</h2>
        <p class="mt-2 text-sm text-stone-600">Mulai pilih pastry dari katalog untuk membuat pesanan.</p>
        <a href="{{ route('products.index') }}" class="bunkue-button-primary mt-6">Jelajahi produk</a>
    </div>
    @else
    <div class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="bunkue-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-stone-200 px-5 py-4 sm:px-6">
                <h2 class="text-sm font-semibold text-stone-900">Produk dipilih</h2>
                <span class="text-sm text-stone-500">{{ $cart->items->sum('qty') }} item</span>
            </div>
            <div class="divide-y divide-stone-200">
                @foreach ($cart->items as $item)
                <article class="flex gap-4 p-5 sm:p-6">
                    <div class="size-20 shrink-0 overflow-hidden rounded-xl bg-[#eee7df] sm:size-24">
                        @if ($item->product->images->first())
                        <img src="{{ $item->product->images->first()->url }}" alt="{{ $item->product->name }}" class="size-full object-cover">
                        @else
                        <span class="grid size-full place-items-center text-stone-400"><x-lucide-image class="size-8" aria-hidden="true" /></span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-semibold text-stone-900">{{ $item->product->name }}</h3>
                        <p class="mt-1 text-sm text-stone-500">Rp {{ number_format($item->price_snapshot, 0, ',', '.') }} / item</p>
                        <p class="mt-2 text-sm font-semibold text-stone-900 sm:hidden">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                        <div class="mt-4 flex flex-wrap items-end gap-2">
                            <form method="POST" action="{{ route('cart-items.update', $item) }}" class="flex items-end gap-2">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label for="quantity-{{ $item->id }}" class="mb-1 block text-xs text-stone-500">Jumlah</label>
                                    <input id="quantity-{{ $item->id }}" type="number" name="quantity" min="1" max="{{ min($item->product->stock, 100) }}" value="{{ $item->qty }}" required class="w-20 text-center">
                                </div>
                                <button type="submit" class="bunkue-button-secondary">Perbarui</button>
                            </form>
                            <form method="POST" action="{{ route('cart-items.destroy', $item) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="min-h-11 px-2 text-sm font-medium text-red-700 hover:underline">Hapus</button>
                            </form>
                        </div>
                    </div>
                    <p class="hidden shrink-0 text-right text-sm font-semibold text-stone-900 sm:block">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                </article>
                @endforeach
            </div>
        </div>

        <aside class="bunkue-card p-6 lg:sticky lg:top-24">
            <h2 class="text-lg font-semibold text-stone-900">Ringkasan belanja</h2>
            <div class="mt-5 flex justify-between gap-3 text-sm text-stone-600">
                <span>Subtotal</span>
                <span>Rp {{ number_format($cart->total, 0, ',', '.') }}</span>
            </div>
            <div class="mt-5 flex justify-between gap-3 border-t border-stone-200 pt-5 font-semibold text-stone-900">
                <span>Total produk</span>
                <span>Rp {{ number_format($cart->total, 0, ',', '.') }}</span>
            </div>
            <p class="mt-3 text-xs leading-5 text-stone-500">Pilihan pengambilan dan informasi penerima diisi pada langkah berikutnya.</p>
            <a href="{{ route('checkout.create') }}" class="bunkue-button-primary mt-6 w-full">
                Lanjut ke checkout
                <x-lucide-arrow-right class="size-4" aria-hidden="true" />
            </a>
        </aside>
    </div>
    @endif
</section>
@endsection