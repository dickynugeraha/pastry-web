<x-guest-layout>
    <p class="bunkue-kicker">Verifikasi akun</p>
    <h1 class="mt-3 font-serif text-3xl text-stone-900">Periksa email Anda</h1>
    <p class="mt-2 text-sm leading-6 text-stone-600">Klik tautan verifikasi yang kami kirim ke email Anda untuk menyelesaikan pendaftaran.</p>
    @if (session('status') == 'verification-link-sent')
        <p role="status" class="mt-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">Tautan verifikasi baru telah dikirim.</p>
    @endif
    <form method="POST" action="{{ route('verification.send') }}" class="mt-7">
        @csrf
        <button type="submit" class="bunkue-button-primary w-full">Kirim ulang email</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-medium text-stone-600 hover:underline">Keluar</button>
    </form>
</x-guest-layout>
