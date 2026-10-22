@extends('layouts.admin') @section('title', 'Edit Produk') @section('content')<div class="max-w-4xl"><a href="{{ route('admin.products.show', $product) }}" class="text-sm font-semibold text-amber-700">← Detail produk</a>
  <h1 class="mt-4 text-3xl font-bold">Edit produk</h1>
  <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="mt-8 rounded-2xl bg-white p-6 shadow-sm">@csrf @method('PUT') @include('admin.products.form')</form>
</div>@endsection