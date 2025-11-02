@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-3xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Buat Order</h1>
    <a href="{{ route('orders.index') }}" class="px-3 py-2 bg-gray-200 rounded">Kembali</a>
  </div>

  <form action="{{ route('orders.store') }}" method="POST" class="bg-white p-4 rounded shadow">
    @csrf

    {{-- validation errors --}}
    @if($errors->any())
      <div class="mb-3 p-3 bg-red-50 text-red-700 rounded">
        <ul class="list-disc pl-5">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm">Nama Customer</label>
        <input name="customer_name" value="{{ old('customer_name') }}" required class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Email</label>
        <input name="email" value="{{ old('email') }}" type="email" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Telepon</label>
        <input name="phone" value="{{ old('phone') }}" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Product</label>
        <select name="product_id" class="mt-1 block w-full rounded border-gray-200">
          <option value="">-- pilih product --</option>
          @foreach($products as $p)
            <option value="{{ $p->id }}" @if(old('product_id') == $p->id) selected @endif>{{ $p->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm">Waktu Penjemputan</label>
        <input name="pickup_time" value="{{ old('pickup_time') }}" type="datetime-local" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Waktu Sampai (opsional)</label>
        <input name="arrival_time" value="{{ old('arrival_time') }}" type="datetime-local" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Estimasi Durasi (menit)</label>
        <input name="estimated_duration_minutes" value="{{ old('estimated_duration_minutes') }}" type="number" min="1" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Jumlah Mobil (opsional)</label>
        <input name="vehicle_count" value="{{ old('vehicle_count',1) }}" type="number" min="1" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Jumlah Dewasa</label>
        <input name="adults" value="{{ old('adults',1) }}" type="number" min="0" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Jumlah Anak-anak</label>
        <input name="children" value="{{ old('children',0) }}" type="number" min="0" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Jumlah Bayi</label>
        <input name="babies" value="{{ old('babies',0) }}" type="number" min="0" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm">Tempat Penjemputan</label>
        <input name="pickup_location" value="{{ old('pickup_location') }}" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm">Tempat Tujuan</label>
        <input name="destination" value="{{ old('destination') }}" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm">Catatan</label>
        <textarea name="note" class="mt-1 block w-full rounded border-gray-200" rows="3">{{ old('note') }}</textarea>
      </div>
    </div>

    <div class="mt-4 flex items-center space-x-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Buat Order</button>
      <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-gray-200 rounded">Batal</a>
    </div>
  </form>
</div>
@endsection
