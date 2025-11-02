@extends('layouts.app')

@section('title','Edit Order - JTB Tours')

@section('content')
<div class="max-w-3xl mx-auto">
  <h2 class="text-xl font-semibold mb-4">Edit Order #{{ $order->id }}</h2>

  @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded mb-4">
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form action="{{ route('orders.update',$order->id) }}" method="POST" class="bg-white p-6 rounded shadow">
    @csrf @method('PUT')
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block mb-2">Nama Customer</label>
        <input type="text" name="customer_name" value="{{ old('customer_name',$order->customer_name) }}" class="w-full border p-2 rounded" required>
      </div>

      <div>
        <label class="block mb-2">Email</label>
        <input type="email" name="email" value="{{ old('email',$order->email) }}" class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block mb-2">Telepon</label>
        <input type="text" name="phone" value="{{ old('phone',$order->phone) }}" class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block mb-2">Jumlah Orang (total)</label>
        <input type="number" name="passengers" value="{{ old('passengers',$order->passengers) }}" min="1" class="w-full border p-2 rounded" required>
      </div>

      <div>
        <label class="block mb-2">Dewasa</label>
        <input type="number" name="adults" value="{{ old('adults',$order->adults) }}" min="0" class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block mb-2">Anak-anak</label>
        <input type="number" name="children" value="{{ old('children',$order->children) }}" min="0" class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block mb-2">Bayi</label>
        <input type="number" name="babies" value="{{ old('babies',$order->babies) }}" min="0" class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block mb-2">Jumlah Mobil</label>
        <input type="number" name="vehicle_count" value="{{ old('vehicle_count',$order->vehicle_count) }}" min="1" class="w-full border p-2 rounded">
      </div>

      <div class="col-span-2">
        <label class="block mb-2">Product</label>
        <select name="product_id" class="w-full border p-2 rounded" required>
          <option value="">-- Pilih Product --</option>
          @foreach($products as $p)
            <option value="{{ $p->id }}" {{ (old('product_id',$order->product_id) == $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block mb-2">Waktu Penjemputan</label>
        <input type="datetime-local" name="pickup_time" value="{{ old('pickup_time', $order->pickup_time ? $order->pickup_time->format('Y-m-d\TH:i') : '') }}" class="w-full border p-2 rounded" required>
      </div>

      <div>
        <label class="block mb-2">Waktu Sampai (opsional)</label>
        <input type="datetime-local" name="arrival_time" value="{{ old('arrival_time', $order->arrival_time ? $order->arrival_time->format('Y-m-d\TH:i') : '') }}" class="w-full border p-2 rounded">
      </div>

      <div class="col-span-2">
        <label class="block mb-2">Estimasi Durasi (menit)</label>
        <input type="number" name="estimated_duration_minutes" value="{{ old('estimated_duration_minutes',$order->estimated_duration_minutes) }}" min="1" class="w-full border p-2 rounded">
      </div>

      <div class="col-span-2">
        <label class="block mb-2">Tempat Penjemputan</label>
        <input type="text" name="pickup_location" value="{{ old('pickup_location',$order->pickup_location) }}" class="w-full border p-2 rounded">
      </div>

      <div class="col-span-2">
        <label class="block mb-2">Tempat Tujuan</label>
        <input type="text" name="destination" value="{{ old('destination',$order->destination) }}" class="w-full border p-2 rounded">
      </div>

      <div class="col-span-2">
        <label class="block mb-2">Catatan</label>
        <textarea name="note" rows="3" class="w-full border p-2 rounded">{{ old('note',$order->note) }}</textarea>
      </div>
    </div>

    <div class="mt-4 flex items-center space-x-2">
      <button class="bg-blue-600 text-white px-4 py-2 rounded">Simpan Perubahan</button>
      <a href="{{ route('orders.index') }}" class="px-4 py-2 border rounded">Batal</a>
    </div>
  </form>
</div>
@endsection
