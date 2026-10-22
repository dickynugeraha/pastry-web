<x-guest-layout>
    <p class="bunkue-kicker">Bantuan akun</p>
    <h1 class="mt-3 font-serif text-3xl text-stone-900">Lupa password?</h1>
    <p class="mt-2 text-sm leading-6 text-stone-600">Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat password baru.</p>
    <x-auth-session-status class="mt-5" :status="session('status')" />
    <form method="POST" action="{{ route('password.email') }}" class="mt-7 grid gap-5">
        @csrf
        <div>
            <label for="email" class="mb-2 block text-sm font-medium text-stone-700">Alamat email</label>
            <input id="email" class="w-full" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <button type="submit" class="bunkue-button-primary w-full">Kirim tautan reset</button>
    </form>
    <a href="{{ route('login') }}" class="mt-6 block text-center text-sm font-medium text-[#775744] hover:underline">Kembali ke masuk</a>
</x-guest-layout>
