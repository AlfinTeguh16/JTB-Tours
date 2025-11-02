@extends('layouts.app')

@section('title','Detail Kendaraan')

@section('content')
<div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
  <h2 class="text-xl font-semibold mb-4">{{ $vehicle->brand }} - {{ $vehicle->plate_number }}</h2>

  <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
    <dt class="text-sm text-gray-500">Merk</dt><dd class="font-medium">{{ $vehicle->brand }}</dd>
    <dt class="text-sm text-gray-500">Tipe</dt><dd class="font-medium">{{ $vehicle->type }}</dd>
    <dt class="text-sm text-gray-500">Plat</dt><dd class="font-medium">{{ $vehicle->plate_number }}</dd>
    <dt class="text-sm text-gray-500">Warna</dt><dd class="font-medium">{{ $vehicle->color }}</dd>
    <dt class="text-sm text-gray-500">Tahun</dt><dd class="font-medium">{{ $vehicle->year }}</dd>
    <dt class="text-sm text-gray-500">Kapasitas</dt><dd class="font-medium">{{ $vehicle->capacity }}</dd>
    <dt class="text-sm text-gray-500">Status</dt><dd class="font-medium capitalize">{{ $vehicle->status }}</dd>
  </dl>

  <div class="mt-6">
    <a href="{{ route('vehicles.edit', $vehicle->id) }}" class="bg-indigo-600 text-white px-3 py-2 rounded">Edit</a>
    <a href="{{ route('vehicles.index') }}" class="ml-2 px-3 py-2 border rounded">Kembali</a>
  </div>
</div>
@endsection
