{{--
    Gallery — thumbnails that open a full-screen lightbox with prev/next navigation.
      images   array of items. Each item is either a URL string, or an assoc array:
               ['src' => full image, 'thumb' => optional smaller image, 'alt' => description,
                'badge' => optional thumbnail label]
      columns  thumbnail grid columns (default 3)
      rounded  corner rounding utility for thumbnails (default rounded-lg)
      layout   grid (default) | wrap for fixed-size thumbnails
      thumbnailClass  optional thumbnail size utilities for the wrap layout
    A11y: thumbnails are real <button>s; the lightbox is role="dialog" aria-modal with a focus
          trap (x-trap), Escape to close, ←/→ to navigate, and a polite live counter.
          The image also supports horizontal pointer swipes.
          Directional chevrons mirror under RTL via .blat-rtl-flip.
--}}
@props([
    'images' => [],
    'columns' => 3,
    'rounded' => 'rounded-lg',
    'layout' => 'grid',
    'thumbnailClass' => '',
    'title' => null,
])

@php
    $images = collect($images)->map(function ($img) {
        if (is_string($img)) {
            return ['src' => $img, 'thumb' => $img, 'alt' => '', 'badge' => null];
        }

        return [
            'src' => $img['src'] ?? '',
            'thumb' => $img['thumb'] ?? ($img['src'] ?? ''),
            'alt' => $img['alt'] ?? '',
            'badge' => $img['badge'] ?? null,
        ];
    })->values()->all();

    $gridCols = [
        1 => 'grid-cols-1', 2 => 'grid-cols-2', 3 => 'grid-cols-3',
        4 => 'grid-cols-4', 5 => 'grid-cols-5', 6 => 'grid-cols-6',
    ][max(1, (int) $columns)] ?? 'grid-cols-3';
@endphp

<div
    data-slot="gallery"
    x-data="{
        images: @js($images),
        open: false,
        index: 0,
        pointerStartX: null,
        show(i) { this.index = i; this.open = true; },
        close() { this.open = false; this.pointerStartX = null; },
        next() { if (this.images.length > 1) this.index = (this.index + 1) % this.images.length; },
        prev() { if (this.images.length > 1) this.index = (this.index - 1 + this.images.length) % this.images.length; },
        startSwipe(event) {
            if (this.images.length < 2 || (event.pointerType === 'mouse' && event.button !== 0)) return;
            this.pointerStartX = event.clientX;
        },
        endSwipe(event) {
            if (this.pointerStartX === null) return;
            const distance = event.clientX - this.pointerStartX;
            this.pointerStartX = null;
            if (distance <= -50) this.next();
            else if (distance >= 50) this.prev();
        },
        onKeydown(event) {
            if (!this.open) return;
            if (event.key === 'Escape') this.close();
            if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
                event.preventDefault();
                if (event.key === 'ArrowRight') this.next();
                else this.prev();
            }
        },
        get current() { return this.images[this.index] || {}; }
    }"
    {{ $attributes->twMerge('block') }}
>
    <div class="{{ $layout === 'wrap' ? 'flex flex-wrap gap-3' : 'grid '.$gridCols.' gap-2' }}">
        @foreach ($images as $i => $img)
            <button
                type="button"
                @click="show({{ $i }})"
                aria-label="{{ $img['alt'] !== '' ? __('View image: :alt', ['alt' => $img['alt']]) : __('View image :number', ['number' => $i + 1]) }}"
                class="group bg-muted focus-visible:ring-ring relative aspect-square overflow-hidden {{ $rounded }} {{ $thumbnailClass }} border outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
            >
                <img src="{{ $img['thumb'] }}" alt="{{ $img['alt'] }}" loading="lazy" class="size-full object-cover transition-transform duration-300 group-hover:scale-105" />
                @if ($img['badge'])
                    <span class="pointer-events-none absolute bottom-1.5 left-1.5 rounded-md bg-white/95 px-1.5 py-0.5 text-[10px] font-medium text-stone-800">{{ $img['badge'] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50"
            @keydown.window="onKeydown($event)"
            @pointerup.window="endSwipe($event)"
            @pointercancel.window="pointerStartX = null"
        >
            <div x-show="open" @click="close()" aria-hidden="true" x-transition.opacity class="fixed inset-0 bg-black/90"></div>

            <div
                x-show="open"
                x-trap.noscroll.inert="open"
                role="dialog"
                aria-modal="true"
                aria-label="{{ $title ?? __('Image gallery') }}"
                tabindex="-1"
                x-transition.opacity
                class="fixed inset-0 z-50 flex flex-col"
            >
                <div class="flex items-center justify-between p-4 text-white">
                    <span class="text-sm tabular-nums" aria-live="polite" x-text="(index + 1) + ' / ' + images.length"></span>
                    <button type="button" @click="close()" aria-label="{{ __('Close') }}" class="rounded-md p-2 opacity-80 outline-none transition-opacity hover:opacity-100 focus-visible:ring-2 focus-visible:ring-white/60">
                        <x-lucide-x class="size-5" />
                    </button>
                </div>

                <div class="relative flex min-h-0 flex-1 items-center justify-center px-4 pb-6" @click.self="close()">
                    <button
                        type="button"
                        x-show="images.length > 1"
                        @click="prev()"
                        aria-label="{{ __('Previous image') }}"
                        class="absolute start-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-white outline-none backdrop-blur transition hover:bg-white/20 focus-visible:ring-2 focus-visible:ring-white/70"
                    >
                        <x-lucide-chevron-left class="blat-rtl-flip size-6" />
                    </button>

                    <img :src="current.src" :alt="current.alt" @pointerdown="startSwipe($event)" draggable="false" class="max-h-full max-w-full touch-pan-y select-none rounded-lg object-contain shadow-2xl" />

                    <button
                        type="button"
                        x-show="images.length > 1"
                        @click="next()"
                        aria-label="{{ __('Next image') }}"
                        class="absolute end-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-white outline-none backdrop-blur transition hover:bg-white/20 focus-visible:ring-2 focus-visible:ring-white/70"
                    >
                        <x-lucide-chevron-right class="blat-rtl-flip size-6" />
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
