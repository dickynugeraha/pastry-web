<x-guest-layout>
    <p class="bunkue-kicker">Akun Bunkue</p>
    <h1 class="mt-3 font-serif text-3xl text-stone-900">Selamat datang kembali</h1>
    <p class="mt-2 text-sm leading-6 text-stone-600">Masuk untuk melanjutkan belanja dan melihat pesanan Anda.</p>

    <x-auth-session-status class="mt-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-7 grid gap-5">
        @csrf
        <div>
            <label for="email" class="mb-2 block text-sm font-medium text-stone-700">Alamat email</label>
            <input id="email" class="w-full" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <div class="mb-2 flex items-center justify-between gap-3">
                <label for="password" class="text-sm font-medium text-stone-700">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-[#775744] hover:underline">Lupa password?</a>
                @endif
            </div>
            <input id="password" class="w-full" type="password" name="password" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <label class="flex items-center gap-2 text-sm text-stone-600">
            <input type="checkbox" name="remember" class="rounded border-stone-300">
            Ingat saya
        </label>
        <button type="submit" class="bunkue-button-primary w-full">Masuk</button>
    </form>
    <p class="mt-6 text-center text-sm text-stone-600">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-[#775744] hover:underline">Daftar</a></p>
</x-guest-layout>
