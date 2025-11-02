@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-3xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Buat Assignment</h1>
    <a href="{{ route('assignments.index') }}" class="px-3 py-2 bg-gray-200 rounded">Kembali</a>
  </div>

  <form action="{{ route('assignments.store') }}" method="POST" class="bg-white p-4 rounded shadow">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm">Pilih Order</label>
        <select name="order_id" required class="mt-1 block w-full rounded border-gray-200">
          <option value="">-- pilih order --</option>
          @foreach($orders as $o)
            <option value="{{ $o->id }}" @if(isset($order) && $order->id == $o->id) selected @endif>
              #{{ $o->id }} — {{ $o->customer_name }} / {{ $o->pickup_time ? \Carbon\Carbon::parse($o->pickup_time)->format('d M Y H:i') : '-' }}
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm">Pilih Driver</label>
        <select name="driver_id" required class="mt-1 block w-full rounded border-gray-200">
          <option value="">-- pilih driver --</option>
          @foreach($drivers as $d)
            <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->phone }})</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm">Pilih Guide (opsional)</label>
        <select name="guide_id" class="mt-1 block w-full rounded border-gray-200">
          <option value="">-- tidak ada --</option>
          @foreach($guides as $g)
            <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->phone }})</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm">Catatan</label>
        <input name="note" class="mt-1 block w-full rounded border-gray-200" />
      </div>
    </div>

    <div class="mt-4">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Buat Assignment</button>
      <a href="{{ route('assignments.index') }}" class="ml-2 px-4 py-2 bg-gray-200 rounded">Batal</a>
    </div>
  </form>
</div>
@endsection
