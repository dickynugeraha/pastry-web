<x-guest-layout>
    <p class="bunkue-kicker">Bantuan akun</p>
    <h1 class="mt-3 font-serif text-3xl text-stone-900">Buat password baru</h1>
    <p class="mt-2 text-sm leading-6 text-stone-600">Pilih password yang kuat untuk akun Anda.</p>
    <form method="POST" action="{{ route('password.store') }}" class="mt-7 grid gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <label for="email" class="mb-2 block text-sm font-medium text-stone-700">Alamat email</label>
            <input id="email" class="w-full" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <label for="password" class="mb-2 block text-sm font-medium text-stone-700">Password baru</label>
            <input id="password" class="w-full" type="password" name="password" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <label for="password_confirmation" class="mb-2 block text-sm font-medium text-stone-700">Konfirmasi password</label>
            <input id="password_confirmation" class="w-full" type="password" name="password_confirmation" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        <button type="submit" class="bunkue-button-primary w-full">Simpan password baru</button>
    </form>
</x-guest-layout>