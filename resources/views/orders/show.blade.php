@extends('layouts.app')

@section('title','Detail Order - JTB Tours')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
  <h2 class="text-xl font-semibold mb-4">Order #{{ $order->id }}</h2>

  <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
    <dt class="text-sm text-gray-500">Nama</dt>
    <dd class="font-medium">{{ $order->customer_name }}</dd>

    <dt class="text-sm text-gray-500">Email</dt>
    <dd class="font-medium">{{ $order->email }}</dd>

    <dt class="text-sm text-gray-500">Telepon</dt>
    <dd class="font-medium">{{ $order->phone }}</dd>

    <dt class="text-sm text-gray-500">Pickup</dt>
    <dd class="font-medium">{{ $order->pickup_time?->format('Y-m-d H:i') }}</dd>

    <dt class="text-sm text-gray-500">Arrival</dt>
    <dd class="font-medium">{{ $order->arrival_time?->format('Y-m-d H:i') }}</dd>

    <dt class="text-sm text-gray-500">Estimasi (menit)</dt>
    <dd class="font-medium">{{ $order->estimated_duration_minutes }}</dd>

    <dt class="text-sm text-gray-500">Passengers</dt>
    <dd class="font-medium">Total: {{ $order->passengers }}, D:{{ $order->adults }}, C:{{ $order->children }}, B:{{ $order->babies }}</dd>

    <dt class="text-sm text-gray-500">Pickup Location</dt>
    <dd class="font-medium">{{ $order->pickup_location }}</dd>

    <dt class="text-sm text-gray-500">Destination</dt>
    <dd class="font-medium">{{ $order->destination }}</dd>

    <dt class="text-sm text-gray-500">Product</dt>
    <dd class="font-medium">{{ $order->product?->name }}</dd>

    <dt class="text-sm text-gray-500">Vehicle Count</dt>
    <dd class="font-medium">{{ $order->vehicle_count }}</dd>

    <dt class="text-sm text-gray-500">Status</dt>
    <dd class="font-medium capitalize">{{ $order->status }}</dd>

    <dt class="text-sm text-gray-500">Catatan</dt>
    <dd class="font-medium">{{ $order->note }}</dd>
  </dl>

  <div class="mt-6">
    <a href="{{ route('orders.edit', $order->id) }}" class="bg-indigo-600 text-white px-3 py-2 rounded">Edit</a>
    <a href="{{ route('orders.index') }}" class="ml-2 px-3 py-2 border rounded">Kembali</a>
  </div>
</div>
@endsection
