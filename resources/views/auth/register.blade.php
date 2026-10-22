<x-guest-layout>
    <p class="bunkue-kicker">Akun baru</p>
    <h1 class="mt-3 font-serif text-3xl text-stone-900">Daftar di Bunkue</h1>
    <p class="mt-2 text-sm leading-6 text-stone-600">Buat akun untuk menyimpan keranjang dan memantau pesanan.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-7 grid gap-5">
        @csrf
        <div>
            <label for="name" class="mb-2 block text-sm font-medium text-stone-700">Nama lengkap</label>
            <input id="name" class="w-full" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <label for="email" class="mb-2 block text-sm font-medium text-stone-700">Alamat email</label>
            <input id="email" class="w-full" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <label for="password" class="mb-2 block text-sm font-medium text-stone-700">Password</label>
            <input id="password" class="w-full" type="password" name="password" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <label for="password_confirmation" class="mb-2 block text-sm font-medium text-stone-700">Konfirmasi password</label>
            <input id="password_confirmation" class="w-full" type="password" name="password_confirmation" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        <button type="submit" class="bunkue-button-primary w-full">Buat akun</button>
    </form>
    <p class="mt-6 text-center text-sm text-stone-600">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-[#775744] hover:underline">Masuk</a></p>
</x-guest-layout>