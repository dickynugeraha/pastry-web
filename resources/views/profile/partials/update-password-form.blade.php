<section>
    <div class="flex items-start gap-4">
        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-stone-100 text-stone-700">
            <x-lucide-lock-keyhole class="size-5" aria-hidden="true" />
        </span>
        <div>
            <h2 class="font-serif text-2xl font-semibold text-stone-900">Keamanan akun</h2>
            <p class="mt-1 text-sm leading-6 text-stone-500">Gunakan password yang panjang dan unik untuk menjaga akun tetap aman.</p>
        </div>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="mt-8 space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-sm font-semibold text-stone-700">Password saat ini</label>
            <input id="update_password_current_password" name="current_password" type="password" class="mt-2 block w-full" autocomplete="current-password">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="update_password_password" class="block text-sm font-semibold text-stone-700">Password baru</label>
                <input id="update_password_password" name="password" type="password" class="mt-2 block w-full" autocomplete="new-password">
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="update_password_password_confirmation" class="block text-sm font-semibold text-stone-700">Konfirmasi password</label>
                <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-2 block w-full" autocomplete="new-password">
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 pt-2">
            <button type="submit" class="bunkue-button-primary inline-flex items-center gap-2 px-5 py-3">
                <x-lucide-shield-check class="size-4" aria-hidden="true" />
                Perbarui password
            </button>

        </div>
    </form>
</section>