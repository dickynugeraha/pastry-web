<form method="GET" action="{{ $action }}" x-data="{ period: @js(request('period', 'month')) }" class="flex flex-wrap items-end gap-2 text-sm">
    <label class="grid gap-2 text-xs font-medium text-stone-600">Periode
        <select name="period" x-model="period">
            <option value="day">Hari ini</option>
            <option value="week">Minggu ini</option>
            <option value="month">Bulan ini</option>
            <option value="year">Tahun ini</option>
            <option value="custom">Kustom</option>
        </select>
    </label>
    <div x-cloak x-show="period === 'custom'" class="flex flex-wrap gap-2">
        <label class="grid gap-2 text-xs font-medium text-stone-600">Dari
            <input type="date" name="start_date" value="{{ request('start_date') }}">
        </label>
        <label class="grid gap-2 text-xs font-medium text-stone-600">Sampai
            <input type="date" name="end_date" value="{{ request('end_date') }}">
        </label>
    </div>
    <button type="submit" class="bunkue-button-primary">Terapkan</button>
</form>