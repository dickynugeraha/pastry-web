@props(['product', 'cartQuantity' => 0])

<article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white transition hover:border-stone-300 hover:shadow-[0_10px_30px_rgba(45,34,25,0.06)]">
    <a href="{{ route('products.show', $product) }}" class="block overflow-hidden bg-[#eee7df]" aria-label="Lihat {{ $product->name }}">
        @if ($product->primary_image_url)
            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-[1.03]" loading="lazy">
        @else
            <span class="grid aspect-[4/3] place-items-center text-stone-400"><x-lucide-image class="size-12" aria-hidden="true" /></span>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-5">
        <p class="text-xs font-medium text-[#775744]">{{ $product->category?->name ?? 'Pastry' }}</p>
        <a href="{{ route('products.show', $product) }}" class="mt-2 text-base font-semibold leading-snug text-stone-900 hover:text-[#775744]">{{ $product->name }}</a>
        <p class="mt-2 text-base font-semibold text-stone-900">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
        <div class="mt-auto flex items-center justify-between gap-3 pt-5">
            <span class="text-xs {{ $product->stock > 0 ? 'text-stone-500' : 'text-red-700' }}">{{ $product->stock > 0 ? 'Tersedia' : 'Stok habis' }}</span>
            @if ($product->stock > 0)
                @auth
                    <x-store.cart-quantity-dialog :product="$product" :cart-quantity="$cartQuantity">
                        <x-slot:trigger>
                            <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-stone-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-stone-700" aria-label="Pilih jumlah {{ $product->name }} untuk keranjang">
                                <x-lucide-plus class="size-3.5" aria-hidden="true" />
                                Keranjang
                            </button>
                        </x-slot:trigger>
                    </x-store.cart-quantity-dialog>
                @else
                    <a href="{{ route('products.show', $product) }}" class="text-xs font-semibold text-stone-800 hover:text-[#775744]">Lihat detail <span aria-hidden="true">→</span></a>
                @endauth
            @endif
        </div>
    </div>
</article>
