@extends('layouts.admin') @section('title', 'Tambah Produk') @section('content')<div class="max-w-4xl"><a href="{{ route('admin.products.index') }}" class="text-sm font-semibold text-amber-700">← Produk</a>
  <h1 class="mt-4 text-3xl font-bold">Tambah produk</h1>
  <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="mt-8 rounded-2xl bg-white p-6 shadow-sm">@csrf @include('admin.products.form')</form>
</div>@endsection