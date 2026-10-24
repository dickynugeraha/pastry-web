@extends('layouts.store')

@section('title', 'Checkout · Bunkue Pastry')

@section('content')
<section class="bunkue-container py-10 md:py-14">
    <nav aria-label="Tahapan belanja" class="flex items-center gap-2 text-xs font-medium text-stone-500">
        <a href="{{ route('cart.show') }}" class="hover:text-stone-900">Keranjang</a>
        <x-lucide-chevron-right class="size-3.5" aria-hidden="true" />
        <span class="text-stone-900" aria-current="step">Checkout</span>
        <x-lucide-chevron-right class="size-3.5" aria-hidden="true" />
        <span>Pesanan</span>
    </nav>
    <div class="mt-6">
        <p class="bunkue-kicker">Langkah terakhir</p>
        <h1 class="bunkue-page-title mt-3">Checkout</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600">Tentukan cara menerima pesanan Anda, lalu periksa ringkasannya.</p>
    </div>

    @if ($errors->any())
    <div role="alert" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <p class="font-semibold">Periksa kembali informasi berikut:</p>
        <ul class="mt-2 list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('orders.store') }}" x-data="{ delivery: @js(old('delivery_method', 'pickup')), channel: @js(old('payment_channel', 'manual')) }" class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        @csrf
        <div class="bunkue-card p-5 sm:p-7">
            <h2 class="text-lg font-semibold text-stone-900">Cara menerima pesanan</h2>
            <p class="mt-1 text-sm text-stone-600">Pilih salah satu metode di bawah ini.</p>
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-stone-200 p-4 has-[:checked]:border-[#775744] has-[:checked]:bg-[#f8f3ee]">
                    <input type="radio" name="delivery_method" value="pickup" x-model="delivery" class="mt-1">
                    <span><strong class="block text-sm text-stone-900">Ambil sendiri</strong><span class="mt-1 block text-xs leading-5 text-stone-600">Ambil pesanan setelah siap.</span></span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-stone-200 p-4 has-[:checked]:border-[#775744] has-[:checked]:bg-[#f8f3ee]">
                    <input type="radio" name="delivery_method" value="delivery" x-model="delivery" class="mt-1">
                    <span><strong class="block text-sm text-stone-900">Diantar</strong><span class="mt-1 block text-xs leading-5 text-stone-600">Isi alamat dan kontak penerima.</span></span>
                </label>
            </div>

            <div x-cloak x-show="delivery === 'delivery'" class="mt-7 grid gap-5 border-t border-stone-200 pt-6 sm:grid-cols-2">
                <div>
                    <label for="recipient_name" class="mb-2 block text-sm font-medium text-stone-700">Nama penerima</label>
                    <input id="recipient_name" name="recipient_name" value="{{ old('recipient_name', auth()->user()->name) }}" autocomplete="name" :disabled="delivery !== 'delivery'" :required="delivery === 'delivery'" class="w-full">
                    <x-input-error :messages="$errors->get('recipient_name')" class="mt-2" />
                </div>
                <div>
                    <label for="recipient_phone" class="mb-2 block text-sm font-medium text-stone-700">Nomor telepon</label>
                    <input id="recipient_phone" type="tel" name="recipient_phone" value="{{ old('recipient_phone', auth()->user()->phone) }}" autocomplete="tel" :disabled="delivery !== 'delivery'" :required="delivery === 'delivery'" class="w-full">
                    <x-input-error :messages="$errors->get('recipient_phone')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <label for="shipping_address" class="mb-2 block text-sm font-medium text-stone-700">Alamat lengkap</label>
                    <textarea id="shipping_address" name="shipping_address" rows="4" autocomplete="street-address" :disabled="delivery !== 'delivery'" :required="delivery === 'delivery'" class="w-full">{{ old('shipping_address') }}</textarea>
                    <x-input-error :messages="$errors->get('shipping_address')" class="mt-2" />
                </div>
            </div>

            <fieldset class="mt-7 grid gap-4 border-t border-stone-200 pt-6">
                <legend class="text-lg font-semibold text-stone-900">Pembayaran</legend>
                <label class="flex items-center gap-3"><input type="radio" name="payment_channel" value="manual" x-model="channel"><span>Pembayaran manual</span></label>
                @if (config('payments.midtrans.enabled') && filled(config('payments.midtrans.client_key')) && filled(config('payments.midtrans.server_key')))
                <label class="flex items-center gap-3"><input type="radio" name="payment_channel" value="midtrans" x-model="channel"><span>Midtrans — bayar online</span></label>
                @endif
                <div x-show="channel === 'manual'" class="grid gap-3">
                    @foreach (config('payments.manual') as $method => $details)
                    <label class="flex items-start gap-3 rounded-xl border border-stone-200 p-4">
                        <input type="radio" name="manual_payment_method" value="{{ $method }}" :disabled="channel !== 'manual'" :required="channel === 'manual'" @checked(old('manual_payment_method', 'bca') === $method) class="mt-1">
                        <span class="text-sm"><strong>{{ $details['provider'] }}</strong><span class="mt-1 block">{{ $details['destination_number'] }} · {{ $details['account_name'] }}</span></span>
                    </label>
                    @endforeach
                    <p class="text-xs leading-5 text-stone-600">Transfer sesuai total pesanan, lalu unggah bukti pembayaran pada halaman pesanan. Pembayaran diverifikasi admin.</p>
                </div>
            </fieldset>

            <div class="mt-7 border-t border-stone-200 pt-6">
                <label for="notes" class="mb-2 block text-sm font-medium text-stone-700">Catatan pesanan <span class="font-normal text-stone-500">(opsional)</span></label>
                <textarea id="notes" name="notes" rows="3" placeholder="Contoh: tulis pesan singkat pada kemasan" class="w-full">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </div>

        <aside class="bunkue-card p-6 lg:sticky lg:top-24">
            <h2 class="text-lg font-semibold text-stone-900">Ringkasan pesanan</h2>
            <div class="mt-5 divide-y divide-stone-100 border-y border-stone-200">
                @foreach ($cart->items as $item)
                <div class="flex justify-between gap-3 py-3 text-sm">
                    <span class="text-stone-600">{{ $item->qty }} × {{ $item->product->name }}</span>
                    <span class="shrink-0 font-medium text-stone-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
                @endforeach
            </div>
            <div class="mt-5 flex justify-between gap-3 font-semibold text-stone-900">
                <span>Total produk</span>
                <span>Rp {{ number_format($cart->total, 0, ',', '.') }}</span>
            </div>
            <p class="mt-3 text-xs leading-5 text-stone-500">Pastikan jumlah dan informasi penerima sudah benar sebelum membuat pesanan.</p>
            <button type="submit" class="bunkue-button-primary mt-6 w-full">Buat pesanan</button>
            <a href="{{ route('cart.show') }}" class="mt-4 block text-center text-sm font-medium text-stone-600 hover:text-stone-900">Kembali ke keranjang</a>
        </aside>
    </form>
</section>
@endsection
