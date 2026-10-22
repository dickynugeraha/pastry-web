<div class="grid gap-5 sm:grid-cols-2"><label class="block"><span class="text-sm font-semibold">Nama produk</span><input name="name" value="{{ old('name', $product->name ?? '') }}" required class="mt-2 w-full rounded-xl border-stone-200"></label><label class="block"><span class="text-sm font-semibold">Slug</span><input name="slug" value="{{ old('slug', $product->slug ?? '') }}" placeholder="contoh-croissant" class="mt-2 w-full rounded-xl border-stone-200"></label><label class="block"><span class="text-sm font-semibold">Kategori</span><select name="category_id" required class="mt-2 w-full rounded-xl border-stone-200">@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>{{ $category->name }}</option>@endforeach</select></label><label class="block"><span class="text-sm font-semibold">Harga</span><input type="number" name="price" min="0" step="0.01" value="{{ old('price', $product->price ?? '') }}" required class="mt-2 w-full rounded-xl border-stone-200"></label><label class="block"><span class="text-sm font-semibold">Stok</span><input type="number" name="stock" min="0" value="{{ old('stock', $product->stock ?? 0) }}" required class="mt-2 w-full rounded-xl border-stone-200"></label><label class="flex items-center gap-3 pt-7"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) class="rounded border-stone-300 text-amber-600"><span class="text-sm font-semibold">Produk aktif</span></label></div><label class="mt-5 block"><span class="text-sm font-semibold">Deskripsi</span><textarea name="description" rows="5" class="mt-2 w-full rounded-xl border-stone-200">{{ old('description', $product->description ?? '') }}</textarea></label>
@php
$existingPhotos = isset($product)
? $product->images->map(fn ($image): array => [
'id' => $image->id,
'url' => $image->url,
'name' => $product->name,
'primary' => $image->is_primary,
])->all()
: [];
$primaryPhotoId = isset($product) ? $product->images->firstWhere('is_primary', true)?->id : null;
@endphp

<div class="mt-6" x-data="productPhotoPicker(@js($existingPhotos), @js(old('remove_image_ids', [])), @js(old('primary_image_id', $primaryPhotoId)))">
    <h2 class="text-base font-semibold text-stone-900">Foto produk</h2>
    <p class="mt-1 text-sm text-stone-500">JPG, PNG, atau WebP. Maksimal 5 foto, 4 MB per foto.</p>
    <input x-ref="input" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" aria-label="Pilih foto produk" @change="addFiles($event.target.files)">
    <input type="hidden" name="primary_image_id" :value="primaryId ?? ''">
    <template x-for="id in removedIds" :key="id"><input type="hidden" name="remove_image_ids[]" :value="id"></template>

    <div class="mt-5 flex min-h-[168px] flex-wrap content-start gap-3 rounded-2xl transition-colors"
        :class="dragging ? 'bg-blue-50/50 ring-2 ring-dashed ring-blue-400' : ''"
        @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)">
        <button type="button" x-show="totalPhotos < 5" @click="$refs.input.click()"
            class="flex size-[168px] flex-col items-center justify-center rounded-[26px] border border-dashed border-blue-500 bg-blue-50/80 p-3 text-center text-blue-600 transition hover:bg-blue-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            <div class="grid size-14 place-items-center justify-center items-centerz rounded-full bg-blue-600 leading-none text-white shadow-lg shadow-blue-200">
                <span class="text-3xl text-white mb-1" aria-hidden="true">+</span>
            </div>
            <span class="mt-3 text-base font-semibold">Tambah Foto</span>
            <span class="mt-1 text-xs leading-5">Klik atau drag &amp; drop gambar</span>
        </button>

        <template x-for="photo in visibleExisting" :key="'existing-' + photo.id">
            <div class="relative size-[168px] overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm">
                <button type="button" class="size-full" @click="openPreview(photo.url, photo.name)" :aria-label="'Pratinjau foto ' + photo.name">
                    <img :src="photo.url" :alt="photo.name" class="size-full object-cover">
                </button>
                <button type="button" class="absolute right-2 top-2 grid size-8 place-items-center rounded-full bg-slate-800/85 text-xl leading-none text-white hover:bg-slate-900" @click="removeExisting(photo.id)" :aria-label="'Hapus foto ' + photo.name">×</button>
                <button type="button" class="absolute bottom-2 left-2 rounded-full px-2 py-1 text-[10px] font-semibold"
                    :class="primaryId === photo.id ? 'bg-blue-600 text-white' : 'bg-white/95 text-slate-800'"
                    @click="primaryId = photo.id" x-text="primaryId === photo.id ? 'Foto utama' : 'Jadikan utama'"></button>
            </div>
        </template>

        <template x-for="photo in newPhotos" :key="'new-' + photo.id">
            <div class="relative size-[168px] overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm">
                <button type="button" class="size-full" @click="openPreview(photo.url, photo.file.name)" :aria-label="'Pratinjau foto ' + photo.file.name">
                    <img :src="photo.url" :alt="photo.file.name" class="size-full object-cover">
                </button>
                <button type="button" class="absolute right-2 top-2 grid size-8 place-items-center rounded-full bg-slate-800/85 text-xl leading-none text-white hover:bg-slate-900" @click="removeNew(photo.id)" :aria-label="'Hapus foto ' + photo.file.name">×</button>
            </div>
        </template>
    </div>
    <p class="mt-5 text-sm text-slate-400">Klik gambar untuk preview atau drag &amp; drop untuk menambah lebih dari satu foto.</p>
    @isset($product)
    <p class="mt-1 text-xs text-stone-500">Untuk menghapus foto produk, klik × lalu simpan perubahan.</p>
    @endisset
    <p x-cloak x-show="error" x-text="error" role="alert" class="mt-2 text-sm text-rose-700"></p>

    <template x-teleport="body">
        <div x-cloak x-show="previewUrl" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/85 p-5" @click.self="closePreview()" @keydown.escape.window="closePreview()" role="dialog" aria-modal="true" aria-label="Pratinjau foto produk">
            <button type="button" class="absolute right-5 top-5 rounded-full bg-white px-3 py-1 text-xl text-stone-900" @click="closePreview()" aria-label="Tutup pratinjau">×</button>
            <img :src="previewUrl" :alt="previewName" class="max-h-[85vh] max-w-full rounded-xl object-contain">
        </div>
    </template>
</div>

<button class="mt-6 rounded-xl bg-stone-900 px-5 py-3 font-semibold text-white hover:bg-amber-700">Simpan produk</button>