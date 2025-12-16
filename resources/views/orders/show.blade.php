@extends('layouts.app')

@section('title','Detail Order - JTB Tours')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
  
  
  <div class="flex items-center justify-between">
    <h2 class="text-2xl font-bold text-gray-800">Order #{{ $order->id }}</h2>
    <div class="flex space-x-2">
       <a href="{{ route('orders.edit', $order->id) }}" class="px-4 py-2 bg-yellow-500 text-white rounded shadow hover:bg-yellow-600">Edit Order</a>
       <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Kembali</a>
    </div>
  </div>

  
  <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-blue-600">
    <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Informasi Order</h3>
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
        
        <div>
            <dt class="text-xs font-bold text-gray-500 uppercase">Pelanggan</dt>
            <dd class="mt-1 text-gray-900 font-medium">{{ $order->customer_name }}</dd>
            <div class="mt-1 text-sm text-gray-600">
                {{ $order->email ?? '-' }} • {{ $order->phone ?? '-' }}
            </div>
        </div>

        <div>
            <dt class="text-xs font-bold text-gray-500 uppercase">Status Order</dt>
            <dd class="mt-1">
                <span class="px-2 py-1 rounded-full text-xs font-bold
                    {{ $order->status == 'completed' ? 'bg-blue-100 text-blue-800' : 
                      ($order->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                    {{ ucfirst($order->status) }}
                </span>
            </dd>
        </div>

        <div>
            <dt class="text-xs font-bold text-gray-500 uppercase">Layanan / Product</dt>
            <dd class="mt-1 text-gray-900 font-medium">
                {{ $order->product?->name ?? 'N/A' }}
                @if($order->productBranch)
                  <span class="text-sm text-gray-600 block">Rute: {{ $order->productBranch->name }}</span>
                @endif
            </dd>
            @if($order->product && $order->product->is_exclusive)
                <div class="mt-2 flex gap-2">
                    @if($order->product->snack) <span class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded">Snack</span> @endif
                    @if($order->product->water) <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">Water</span> @endif
                    @if($order->product->magazine) <span class="px-2 py-0.5 bg-pink-100 text-pink-700 text-xs rounded">Magazine</span> @endif
                </div>
            @endif
        </div>

        <div>
            <dt class="text-xs font-bold text-gray-500 uppercase">Penumpang & Kendaraan</dt>
            <dd class="mt-1 text-gray-900">
                Total: <strong>{{ $order->passengers }}</strong> orang <br>
                <span class="text-sm text-gray-600">(Dewasa: {{ $order->adults }}, Anak: {{ $order->children }}, Bayi: {{ $order->babies }})</span>
            </dd>
            <div class="mt-1 text-sm">
                Vehicles needed: <strong>{{ $order->vehicle_count }}</strong>
            </div>
        </div>

        <div>
            <dt class="text-xs font-bold text-gray-500 uppercase">Waktu & Durasi</dt>
            <dd class="mt-1 text-gray-900">
                Pickup: {{ $order->pickup_time?->format('d M Y, H:i') }} <br>
                Arrival: {{ $order->arrival_time?->format('d M Y, H:i') ?? '-' }}
            </dd>
            <div class="mt-1 text-sm font-semibold text-blue-700">
                Estimasi Durasi: {{ $order->estimated_duration_minutes }} menit
            </div>
        </div>

        <div>
            <dt class="text-xs font-bold text-gray-500 uppercase">Rute Perjalanan</dt>
            <dd class="mt-1 text-gray-900">
                <span class="text-xs text-gray-500">From:</span> {{ $order->pickup_location }} <br>
                <span class="text-xs text-gray-500">To:</span> {{ $order->destination }}
            </dd>
        </div>

        <div class="col-span-1 md:col-span-2">
            <dt class="text-xs font-bold text-gray-500 uppercase">Catatan</dt>
            <dd class="mt-1 text-gray-700 bg-gray-50 p-3 rounded border border-gray-100">
                {{ $order->note ?: 'Tidak ada catatan.' }}
            </dd>
        </div>

    </dl>
  </div>

  
  <div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex items-center justify-between mb-4 border-b pb-2">
        <h3 class="text-lg font-semibold text-gray-700">Assignments (Penugasan)</h3>
        @if(auth()->user()->role == 'super_admin' || auth()->user()->role == 'staff')
             <a href="{{ route('assignments.create', ['order_id' => $order->id]) }}" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
                + Tambah Assignment
             </a>
        @endif
    </div>

    @if($order->assignments->isEmpty())
        <div class="p-4 bg-gray-50 text-center text-gray-500 rounded border border-dashed border-gray-300">
            Belum ada driver/guide yang ditugaskan untuk order ini.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left">Driver</th>
                        <th class="px-4 py-2 text-left">Guide</th>
                        <th class="px-4 py-2 text-left">Vehicle</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Waktu Mulai - Selesai (Aktual)</th>
                        <th class="px-4 py-2 text-left">Durasi (Act/Est)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($order->assignments as $assign)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $assign->driver->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $assign->guide->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($assign->vehicle)
                                {{ $assign->vehicle->brand }} {{ $assign->vehicle->type }} <br>
                                <span class="text-xs text-gray-500">{{ $assign->vehicle->plate_number }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-xs
                                {{ $assign->status == 'completed' ? 'bg-blue-100 text-blue-800' :
                                   ($assign->status == 'accepted' ? 'bg-green-100 text-green-800' :
                                   ($assign->status == 'declined' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) }}">
                                {{ ucfirst($assign->status) }}
                            </span>
                            @if($assign->status == 'declined')
                                <div class="text-xs text-red-600 mt-1 max-w-xs truncate">{{ $assign->rejection_reason }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-xs text-gray-600">
                                <div>S: {{ $assign->started_at ? $assign->started_at->format('H:i') : '-' }}</div>
                                <div>E: {{ $assign->completed_at ? $assign->completed_at->format('H:i') : '-' }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $actualMin = 0;
                                if ($assign->started_at && $assign->completed_at) {
                                    $actualMin = $assign->completed_at->diffInMinutes($assign->started_at);
                                }
                            @endphp
                            @if($actualMin > 0)
                                <span title="Actual">{{ $actualMin }}m</span>
                                <span class="text-gray-400">/</span>
                            @endif
                            <span title="Estimated" class="font-medium text-gray-700">{{ $order->estimated_duration_minutes }}m</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
  </div>

</div>
@endsection
