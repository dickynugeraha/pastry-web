<section>
    <div class="flex items-start gap-4">
        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-amber-100 text-amber-800">
            <x-lucide-user-round class="size-5" aria-hidden="true" />
        </span>
        <div>
            <h2 class="font-serif text-2xl font-semibold text-stone-900">Informasi profil</h2>
            <p class="mt-1 text-sm leading-6 text-stone-500">Perbarui nama dan alamat email yang terhubung ke akun Anda.</p>
        </div>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-8 space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-sm font-semibold text-stone-700">Nama lengkap</label>
            <input id="name" name="name" type="text" class="mt-2 block w-full" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold text-stone-700">Alamat email</label>
            <input id="email" name="email" type="email" class="mt-2 block w-full" value="{{ old('email', $user->email) }}" required autocomplete="username">
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p>Email Anda belum terverifikasi.</p>
                <button form="send-verification" class="mt-1 font-semibold underline decoration-amber-300 underline-offset-4 transition hover:text-amber-700">
                    Kirim ulang email verifikasi
                </button>

            </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-4 pt-2">
            <button type="submit" class="bunkue-button-primary inline-flex items-center gap-2 px-5 py-3">
                <x-lucide-check class="size-4" aria-hidden="true" />
                Simpan perubahan
            </button>

        </div>
    </form>
</section>