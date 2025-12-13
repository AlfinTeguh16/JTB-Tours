@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Detail Kendaraan</h1>
        <a href="{{ route('vehicles.index') }}" class="px-4 py-2 bg-gray-200 rounded text-sm hover:bg-gray-300">Kembali</a>
    </div>

    <div class="bg-white rounded shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h2 class="text-xl font-bold">{{ $vehicle->brand }} {{ $vehicle->type }}</h2>
                <div class="text-gray-600 text-lg">{{ $vehicle->plate_number }}</div>
                <div class="mt-2 text-sm">
                    <span class="px-2 py-1 rounded text-white
                        {{ $vehicle->status == 'available' ? 'bg-green-500' :
                           ($vehicle->status == 'in_use' ? 'bg-red-500' : 'bg-yellow-500') }}">
                        {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                    </span>
                    <span class="ml-2 text-gray-500">Cap: {{ $vehicle->capacity }}</span>
                </div>
                <!-- Additional default details -->
                 <dl class="grid grid-cols-2 gap-x-6 gap-y-3 mt-4">
                    <dt class="text-sm text-gray-500">Warna</dt><dd class="font-medium">{{ $vehicle->color ?? '-' }}</dd>
                    <dt class="text-sm text-gray-500">Tahun</dt><dd class="font-medium">{{ $vehicle->year ?? '-' }}</dd>
                 </dl>
            </div>
            <div>
                @if($activeAssignment)
                <div class="bg-blue-50 border border-blue-200 rounded p-4">
                    <h3 class="font-semibold text-blue-800 mb-2">Sedang digunakan oleh:</h3>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 font-bold mr-3">
                            {{ substr($activeAssignment->driver->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="font-bold">{{ $activeAssignment->driver->name }}</div>
                            <div class="text-xs text-gray-600">Telp: {{ $activeAssignment->driver->phone ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-blue-700">
                        Order #{{ $activeAssignment->order_id }} — {{ $activeAssignment->order->customer_name }}
                    </div>
                </div>
                @else
                <div class="bg-green-50 border border-green-200 rounded p-4 text-green-800">
                    Kendaraan saat ini tidak sedang digunakan (berdasarkan assignment aktif).
                </div>
                @endif
            </div>
        </div>
        <div class="mt-4">
             <a href="{{ route('vehicles.edit', $vehicle->id) }}" class="bg-indigo-600 text-white px-3 py-2 rounded">Edit Detail</a>
        </div>
    </div>

    <div class="bg-white rounded shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Riwayat Penggunaan ({{ $history->total() }})</h3>
        
        @if($history->isEmpty())
            <p class="text-gray-500 italic">Belum ada riwayat penggunaan.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left bg-gray-50">Tanggal</th>
                            <th class="px-4 py-2 text-left bg-gray-50">Driver</th>
                            <th class="px-4 py-2 text-left bg-gray-50">Customer / Order</th>
                            <th class="px-4 py-2 text-left bg-gray-50">Status</th>
                            <th class="px-4 py-2 text-left bg-gray-50">Durasi (Est)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($history as $h)
                        <tr>
                            <td class="px-4 py-2">{{ $h->assigned_at ? \Carbon\Carbon::parse($h->assigned_at)->format('d M Y H:i') : '-' }}</td>
                            <td class="px-4 py-2">{{ $h->driver->name ?? '-' }}</td>
                            <td class="px-4 py-2">
                                {{ $h->order->customer_name ?? '-' }} (#{{ $h->order_id }})
                            </td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-0.5 rounded text-xs
                                    {{ $h->status == 'completed' ? 'bg-blue-100 text-blue-800' :
                                       ($h->status == 'accepted' ? 'bg-green-100 text-green-800' :
                                       ($h->status == 'declined' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) }}">
                                    {{ ucfirst($h->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                {{ $h->order->estimated_duration_minutes ?? '-' }} min
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $history->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
