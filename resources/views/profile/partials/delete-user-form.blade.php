<section>
    <div class="flex items-start gap-4">
        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-red-50 text-red-700">
            <x-lucide-triangle-alert class="size-5" aria-hidden="true" />
        </span>
        <div>
            <h2 class="font-serif text-2xl text-stone-900">Hapus akun</h2>
            <p class="mt-1 text-sm leading-6 text-stone-600">Penghapusan akun bersifat permanen. Data akun yang terkait akan dihapus.</p>
        </div>
    </div>

    <button
        type="button"
        class="mt-6 inline-flex items-center gap-2 rounded-xl border border-red-200 px-5 py-3 text-sm font-semibold text-red-700 transition hover:border-red-400 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/30"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        <x-lucide-trash-2 class="size-4" aria-hidden="true" />
        Hapus akun saya
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-8">
            @csrf
            @method('delete')

            <div class="flex items-start gap-4">
                <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-red-100 text-red-700">
                    <x-lucide-trash-2 class="size-5" aria-hidden="true" />
                </span>
                <div>
                    <h2 class="font-serif text-2xl font-semibold text-stone-900">Hapus akun secara permanen?</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-500">Masukkan password Anda untuk mengonfirmasi penghapusan akun dan seluruh data yang terkait.</p>
                </div>
            </div>

            <div class="mt-6">
                <label for="delete_account_password" class="block text-sm font-semibold text-stone-700">Password Anda</label>
                <input id="delete_account_password" name="password" type="password" class="mt-2 block w-full" placeholder="Masukkan password" autocomplete="current-password">
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" class="inline-flex items-center justify-center rounded-full border border-stone-300 px-5 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-900 hover:bg-stone-50" x-on:click="$dispatch('close')">
                    Batal
                </button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-red-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/30">
                    <x-lucide-trash-2 class="size-4" aria-hidden="true" />
                    Hapus akun
                </button>
            </div>
        </form>
    </x-modal>
</section>