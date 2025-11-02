@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-2xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Edit Product</h1>
    <a href="{{ route('products.index') }}" class="px-3 py-2 bg-gray-200 rounded">Kembali</a>
  </div>

  <form action="{{ route('products.update', $product) }}" method="POST" class="bg-white p-4 rounded shadow">
    @csrf @method('PUT')

    <div class="grid grid-cols-1 gap-3">
      <div>
        <label class="block text-sm">Nama</label>
        <input name="name" value="{{ old('name', $product->name) }}" required class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Kapasitas (jumlah penumpang)</label>
        <input name="capacity" type="number" min="1" value="{{ old('capacity', $product->capacity) }}" required class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Deskripsi</label>
        <textarea name="description" rows="4" class="mt-1 block w-full rounded border-gray-200">{{ old('description', $product->description) }}</textarea>
      </div>
    </div>

    <div class="mt-4">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Update</button>
      <a href="{{ route('products.index') }}" class="ml-2 px-4 py-2 bg-gray-200 rounded">Batal</a>
    </div>
  </form>
</div>
@endsection
