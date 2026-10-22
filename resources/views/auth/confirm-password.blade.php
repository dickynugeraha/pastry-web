<x-guest-layout>
    <p class="bunkue-kicker">Keamanan akun</p>
    <h1 class="mt-3 font-serif text-3xl text-stone-900">Konfirmasi password</h1>
    <p class="mt-2 text-sm leading-6 text-stone-600">Masukkan password untuk melanjutkan ke area ini.</p>
    <form method="POST" action="{{ route('password.confirm') }}" class="mt-7 grid gap-5">
        @csrf
        <div>
            <label for="password" class="mb-2 block text-sm font-medium text-stone-700">Password</label>
            <input id="password" class="w-full" type="password" name="password" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <button type="submit" class="bunkue-button-primary w-full">Lanjutkan</button>
    </form>
</x-guest-layout>
