@props(['product', 'cartQuantity' => 0])

@php
    $remainingStock = max(0, $product->stock - $cartQuantity);
    $maxQuantity = min($remainingStock, 100);
@endphp

<x-ui.dialog>
    <x-ui.dialog-trigger>
        {{ $trigger }}
    </x-ui.dialog-trigger>

    <x-ui.dialog-content class="border-stone-200 bg-white text-stone-900 sm:max-w-md">
        <x-ui.dialog-header>
            <x-ui.dialog-title>Tambah ke keranjang</x-ui.dialog-title>
            <x-ui.dialog-description class="text-stone-600">{{ $product->name }} · Rp {{ number_format($product->price, 0, ',', '.') }}</x-ui.dialog-description>
        </x-ui.dialog-header>

        @if ($remainingStock > 0)
            <form method="POST" action="{{ route('cart-items.store', $product) }}" class="mt-3 grid gap-5">
                @csrf
                <div>
                    <label for="cart-quantity-{{ $product->id }}" class="mb-2 block text-sm font-medium text-stone-800">Jumlah</label>
                    <input id="cart-quantity-{{ $product->id }}" type="number" name="quantity" min="1" max="{{ $maxQuantity }}" value="1" required class="w-full text-center">
                    <p class="mt-2 text-xs text-stone-600">{{ $remainingStock }} unit tersedia untuk ditambahkan{{ $cartQuantity > 0 ? ' · '.$cartQuantity.' sudah di keranjang' : '' }}.</p>
                    @if ($remainingStock > 100)
                        <p class="mt-1 text-xs text-stone-500">Maksimal 100 unit setiap kali menyimpan.</p>
                    @endif
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="open = false" class="bunkue-button-secondary">Batal</button>
                    <button type="submit" class="bunkue-button-primary">Simpan</button>
                </div>
            </form>
        @else
            <div class="mt-3 grid gap-4">
                <p class="text-sm text-stone-600">Seluruh stok produk ini sudah ada di keranjang Anda.</p>
                <a href="{{ route('cart.show') }}" class="bunkue-button-primary">Lihat keranjang</a>
            </div>
        @endif
    </x-ui.dialog-content>
</x-ui.dialog>
