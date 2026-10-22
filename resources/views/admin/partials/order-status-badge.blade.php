@php
$color = match ($status) {
\App\Enums\ShippingStatus::PENDING => 'bg-amber-100 text-amber-900',
\App\Enums\ShippingStatus::PROCESSING => 'bg-sky-100 text-sky-800',
\App\Enums\ShippingStatus::DELIVERING => 'bg-indigo-100 text-indigo-800',
\App\Enums\ShippingStatus::READY_FOR_PICKUP => 'bg-violet-100 text-violet-800',
\App\Enums\ShippingStatus::COMPLETED => 'bg-emerald-100 text-emerald-800',
\App\Enums\ShippingStatus::CANCELLED => 'bg-rose-100 text-rose-800',
};
@endphp
<span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $color }}">{{ $status->label() }}</span>