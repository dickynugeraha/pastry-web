@extends('layouts.admin')

@section('title', 'Pengguna · Admin')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="bunkue-kicker">Manajemen akses</p><h1 class="mt-2 text-3xl text-stone-900">Pengguna</h1><p class="mt-2 text-sm text-stone-600">Lihat akun pelanggan dan administrator.</p></div>
        <a href="{{ route('admin.users.create') }}" class="bunkue-button-primary"><x-lucide-plus class="size-4" aria-hidden="true" /> Tambah pengguna</a>
    </div>
    <form method="GET" action="{{ route('admin.users.index') }}" class="mt-7 flex flex-wrap items-end gap-3">
        <label class="grid gap-2 text-xs font-medium text-stone-600">Cari pengguna
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama atau email">
        </label>
        <label class="grid gap-2 text-xs font-medium text-stone-600">Peran
            <select name="role"><option value="">Semua peran</option><option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option><option value="customer" @selected(($filters['role'] ?? '') === 'customer')>Pelanggan</option></select>
        </label>
        <button type="submit" class="bunkue-button-secondary">Terapkan filter</button>
    </form>
    <div class="bunkue-card mt-6 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-stone-200 bg-stone-50 text-xs text-stone-500"><tr><th class="px-5 py-4">Nama</th><th class="px-5 py-4">Email</th><th class="px-5 py-4">Peran</th><th class="px-5 py-4">Aksi</th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-5 py-4 font-semibold">{{ $user->name }}</td>
                        <td class="px-5 py-4 text-stone-600">{{ $user->email }}</td>
                        <td class="px-5 py-4"><span class="rounded-full bg-stone-100 px-3 py-1 text-xs">{{ $user->role === 'admin' ? 'Admin' : 'Pelanggan' }}</span></td>
                        <td class="px-5 py-4"><div class="flex items-center gap-3"><a href="{{ route('admin.users.show', $user) }}" class="font-medium text-[#775744] hover:underline">Detail</a><a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-stone-600 hover:underline">Edit</a>@if (auth()->id() !== $user->id)<form method="POST" action="{{ route('admin.users.destroy', $user) }}">@csrf @method('DELETE')<button type="submit" onclick="return confirm('Hapus pengguna ini?')" class="font-medium text-red-700 hover:underline">Hapus</button></form>@endif</div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-stone-500">Belum ada pengguna yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
@endsection
