<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bunkue Pastry · Akun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bunkue-auth flex min-h-screen flex-col antialiased">
    <x-store-navbar />
    <x-toast-stack />
    <main class="bunkue-container flex flex-1 items-center justify-center py-12 md:py-16">
        <div class="w-full max-w-md">
            <div class="bunkue-card p-6 sm:p-8">{{ $slot }}</div>
            <a href="{{ route('products.index') }}" class="mt-6 block text-center text-sm font-medium text-stone-600 hover:text-stone-900">← Kembali ke katalog</a>
        </div>
    </main>
    <footer class="border-t border-stone-200 py-6 text-center text-xs text-stone-400">© {{ date('Y') }} Bunkue Pastry</footer>
</body>

</html>