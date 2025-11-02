@extends('layouts.app')

@section('title','Detail Product - JTB Tours')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
  <h2 class="text-xl font-semibold mb-4">{{ $product->name }}</h2>

  <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
    <dt class="text-sm text-gray-500">Kapasitas</dt>
    <dd class="font-medium">{{ $product->capacity }}</dd>

    <dt class="text-sm text-gray-500">Deskripsi</dt>
    <dd class="font-medium">{{ $product->description }}</dd>

    <dt class="text-sm text-gray-500">Jumlah Orders</dt>
    <dd class="font-medium">{{ $product->orders()->count() }}</dd>
  </dl>

  <div class="mt-6">
    <a href="{{ route('products.edit', $product->id) }}" class="bg-indigo-600 text-white px-3 py-2 rounded">Edit</a>
    <a href="{{ route('products.index') }}" class="ml-2 px-3 py-2 border rounded">Kembali</a>
  </div>
</div>
@endsection
