@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

@php
    use Carbon\Carbon;
    $month = $month ?? now()->month;
    $year  = $year ?? now()->year;
    $periodLabel = Carbon::create($year, $month, 1)->format('F Y');
@endphp

<div class="max-w-4xl mx-auto p-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Buat Assignment</h1>
    <a href="{{ route('assignments.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
      Kembali
    </a>
  </div>

  <form action="{{ route('assignments.store') }}" method="POST" class="bg-white p-6 rounded-lg shadow-md space-y-6"
        x-data="assignmentForm()">
    @csrf

    @if($errors->any())
      <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded">
        <strong>Terjadi kesalahan:</strong>
        <ul class="list-disc pl-5 mt-1">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      {{-- Pilih Order --}}
      <div class="col-span-1 md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Pilih Order</label>
        <select name="order_id" x-model="orderId" @change="fetchOrderDetails" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          <option value="">-- pilih order --</option>
          @foreach($orders as $o)
            @php
              $pickupDate = $o->pickup_time ? Carbon::parse($o->pickup_time) : null;
              $pickup = $pickupDate ? $pickupDate->format('d M Y H:i') : '-';
              $isOverdue = $pickupDate && $pickupDate->isPast();
            @endphp
            <option value="{{ $o->id }}" @selected(old('order_id', $order->id ?? null) == $o->id) class="{{ $isOverdue ? 'text-red-600 font-bold' : '' }}">
              #{{ $o->id }} — {{ $o->customer_name }} / {{ $pickup }} ({{ $o->passengers }} pax) / {{ $o->vehicle_count }} Unit {{ $isOverdue ? '[OVERDUE]' : '' }}
            </option>
          @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">
            <span x-show="isLoading" class="text-blue-600">Memuat detail order...</span>
            <span x-show="!isLoading && vehicleCount > 0">Membutuhkan <span x-text="vehicleCount" class="font-bold"></span> kendaraan.</span>
        </p>
      </div>

      {{-- Dynamic Rows --}}
      <template x-for="(item, index) in items" :key="index">
         <div class="col-span-1 md:col-span-2 border p-4 rounded-md bg-gray-50 relative">
             <div class="absolute top-2 right-2 text-xs font-bold text-gray-400" x-text="'Kendaraan #' + (index + 1)"></div>
             
             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Select Driver -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Pilih Driver
                        <span class="text-xs text-gray-400 block font-normal" x-show="availableDrivers.length > 0">Tersedia untuk jam order ini</span>
                    </label>
                    <select :name="'assignments['+index+'][driver_id]'" x-model="item.driver_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                      <option value="">-- pilih driver --</option>
                      <template x-for="d in availableDrivers" :key="d.id">
                          <option :value="d.id" x-text="d.text"></option>
                      </template>
                      <option x-show="availableDrivers.length === 0" value="" disabled>Tidak ada driver tersedia</option>
                    </select>
                </div>

                <!-- Select Vehicle -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pilih Kendaraan</label>
                    <select :name="'assignments['+index+'][vehicle_id]'" x-model="item.vehicle_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- pilih kendaraan --</option>
                        <template x-for="v in availableVehicles" :key="v.id">
                            <option :value="v.id" x-text="v.text"></option>
                        </template>
                    </select>
                    <p x-show="availableVehicles.length === 0 && !isLoading && orderId" class="text-xs text-red-500 mt-1">Tidak ada kendaraan tersedia atau belum dimuat.</p>
                </div>

                <!-- Select Guide -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pilih Guide (opsional)</label>
                    <select :name="'assignments['+index+'][guide_id]'" x-model="item.guide_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                      <option value="">-- tidak ada --</option>
                      <template x-for="g in availableGuides" :key="g.id">
                          <option :value="g.id" x-text="g.text"></option>
                      </template>
                    </select>
                </div>

                <!-- Note -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Catatan</label>
                    <input type="text" :name="'assignments['+index+'][note]'" x-model="item.note" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Catatan khusus...">
                </div>
             </div>
         </div>
      </template>
      
      <div x-show="!orderId" class="col-span-1 md:col-span-2 text-center py-8 text-gray-500 border border-dashed rounded bg-gray-50">
          Silakan pilih order terlebih dahulu.
      </div>

    </div>

    <div class="pt-6 border-t flex justify-end space-x-3">
      <a href="{{ route('assignments.index') }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 font-medium my-auto">Batal</a>
      <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 shadow font-medium" :disabled="isLoading || !orderId">Buat Assignment</button>
    </div>
  </form>
</div>

@push('scripts')
<script>
function assignmentForm() {
    return {
        orderId: '{{ old('order_id', $order->id ?? '') }}',
        isLoading: false,
        vehicleCount: 0,
        // Initialize items from old input if valid, otherwise empty array
        items: @json(old('assignments', [])), 
        availableVehicles: [],
        availableDrivers: [],
        availableGuides: [],

        init() {
            // Need to ensure items is array (it might be object if indices are keys)
            if (typeof this.items === 'object' && this.items !== null && !Array.isArray(this.items)) {
                this.items = Object.values(this.items);
            }
            if (!Array.isArray(this.items)) this.items = [];

            if (this.orderId) {
                // Fetch info. If items populated (old data), don't reset them.
                this.fetchOrderDetails(this.items.length === 0);
            }
        },

        fetchOrderDetails(resetItems = true) {
            if (!this.orderId) {
                this.items = [];
                this.vehicleCount = 0;
                this.availableVehicles = [];
                this.availableDrivers = [];
                this.availableGuides = [];
                return;
            }

            this.isLoading = true;
            fetch(`{{ route('assignments.check-vehicle') }}?order_id=${this.orderId}`)
                .then(res => res.json())
                .then(data => {
                    this.availableVehicles = data.vehicles || [];
                    this.availableDrivers = data.drivers || [];
                    this.availableGuides = data.guides || [];
                    this.vehicleCount = data.vehicle_count || 1;
                    
                    if (resetItems) {
                        // Create initial empty rows
                        this.items = Array.from({ length: this.vehicleCount }, () => ({
                            driver_id: '',
                            vehicle_id: '',
                            guide_id: '',
                            note: ''
                        }));
                    }
                    
                    this.isLoading = false;
                })
                .catch(err => {
                    console.error(err);
                    alert('Gagal mengambil detail order.');
                    this.isLoading = false;
                });
        }
    }
}
</script>
@endpush
@endsection
