@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-2xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Edit Kendaraan</h1>
    <a href="{{ route('vehicles.index') }}" class="px-3 py-2 bg-gray-200 rounded">Kembali</a>
  </div>

  <form action="{{ route('vehicles.update', $vehicle) }}" method="POST" class="bg-white p-4 rounded shadow">
    @csrf @method('PUT')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm">Brand</label>
        <input name="brand" value="{{ old('brand', $vehicle->brand) }}" required class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Type</label>
        <input name="type" value="{{ old('type', $vehicle->type) }}" required class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Plate Number</label>
        <input name="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" required class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Color</label>
        <input name="color" value="{{ old('color', $vehicle->color) }}" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Year</label>
        <input name="year" type="number" min="1900" max="{{ date('Y')+1 }}" value="{{ old('year', $vehicle->year) }}" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Capacity</label>
        <input name="capacity" type="number" min="1" value="{{ old('capacity', $vehicle->capacity) }}" required class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm">Status</label>
        <select name="status" class="mt-1 block w-full rounded border-gray-200">
          <option value="available" @if(old('status', $vehicle->status)=='available') selected @endif>Available</option>
          <option value="in_use" @if(old('status', $vehicle->status)=='in_use') selected @endif>In Use</option>
          <option value="maintenance" @if(old('status', $vehicle->status)=='maintenance') selected @endif>Maintenance</option>
        </select>
      </div>
    </div>

    <div class="mt-4">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Update</button>
      <a href="{{ route('vehicles.index') }}" class="ml-2 px-4 py-2 bg-gray-200 rounded">Batal</a>
    </div>
  </form>
</div>
@endsection
