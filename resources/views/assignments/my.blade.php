@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-4xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Tugas Saya</h1>
  </div>

  <div class="space-y-3">
    @forelse($assignments as $a)
      <div class="bg-white p-3 rounded shadow flex items-center justify-between">
        <div>
          <div class="font-medium">#{{ $a->id }} — {{ $a->order->customer_name ?? '-' }}</div>
          <div class="text-xs text-gray-500">{{ $a->order->pickup_time ? \Carbon\Carbon::parse($a->order->pickup_time)->format('d M Y H:i') : '-' }} · {{ $a->order->product?->name ?? '-' }}</div>
        </div>

        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-600">{{ ucfirst($a->status) }}</span>
          <button onclick="openAssignmentModal(@json([
                'id'=>$a->id,
                'order' => [
                  'customer' => $a->order->customer_name ?? '-',
                  'pickup' => $a->order->pickup_time ? \Carbon\Carbon::parse($a->order->pickup_time)->format('d M Y H:i') : '-',
                  'from' => $a->order->pickup_location ?? '-',
                  'to' => $a->order->destination ?? '-',
                  'product' => $a->order->product?->name ?? '-'
                ],
                'driver' => $a->driver?->only(['id','name','phone']),
                'guide' => $a->guide?->only(['id','name','phone']),
                'status' => $a->status,
                'note' => $a->note,
              ]) )" class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Detail & Action</button>
        </div>
      </div>
    @empty
      <div class="bg-white p-4 rounded shadow text-center text-gray-500">Belum ada tugas.</div>
    @endforelse
  </div>
</div>
@endsection
