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
      
      <div class="col-span-1 md:col-span-2">
        <x-select-input name="order_id" label="Pilih Order" x-model="orderId" @change="fetchOrderDetails" required>
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
        </x-select-input>
        <p class="text-xs text-gray-500 mt-1">
            <span x-show="isLoading" class="text-blue-600">Memuat detail order...</span>
            <span x-show="!isLoading && vehicleCount > 0">Membutuhkan <span x-text="vehicleCount" class="font-bold"></span> kendaraan.</span>
        </p>
      </div>

      
      <template x-for="(item, index) in items" :key="index">
         <div class="col-span-1 md:col-span-2 border p-4 rounded-md bg-gray-50 relative">
             <div class="absolute top-2 right-2 text-xs font-bold text-gray-400" x-text="'Kendaraan #' + (index + 1)"></div>
             
             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Pilih Driver
                        <span class="text-xs text-gray-400 block font-normal" x-show="availableDrivers.length > 0">Tersedia untuk jam order ini</span>
                    </label>
                    <select :name="'assignments['+index+'][driver_id]'" x-model="item.driver_id" required 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                      <option value="">-- pilih driver --</option>
                      <template x-for="d in availableDrivers" :key="d.id">
                          <option :value="d.id" x-text="d.text"></option>
                      </template>
                      <option x-show="availableDrivers.length === 0" value="" disabled>Tidak ada driver tersedia</option>
                    </select>
                </div>

                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Kendaraan
                        <span x-show="hasPreassigned" class="text-xs text-green-600">(Locked)</span>
                    </label>

                    
                    <div x-show="hasPreassigned" class="mt-1 p-2 bg-gray-100 border rounded cursor-not-allowed">
                        <span x-text="getVehicleText(item.vehicle_id) || 'Loading...'"></span>
                        <input type="hidden" :name="'assignments['+index+'][vehicle_id]'" :value="item.vehicle_id" :disabled="!hasPreassigned">
                    </div>

                    
                    <div x-show="!hasPreassigned">
                        <select :name="'assignments['+index+'][vehicle_id]'" x-model="item.vehicle_id" :required="!hasPreassigned" :disabled="hasPreassigned"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">-- pilih kendaraan --</option>
                            <template x-for="v in availableVehicles" :key="v.id">
                                <option :value="v.id" x-text="v.text"></option>
                            </template>
                        </select>
                        <p x-show="availableVehicles.length === 0 && !isLoading && orderId" class="text-xs text-red-500 mt-1">Tidak ada kendaraan tersedia.</p>
                    </div>
                </div>

                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Guide (opsional)</label>
                    <select :name="'assignments['+index+'][guide_id]'" x-model="item.guide_id" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                      <option value="">-- tidak ada --</option>
                      <template x-for="g in availableGuides" :key="g.id">
                          <option :value="g.id" x-text="g.text"></option>
                      </template>
                    </select>
                </div>

                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                    <input type="text" :name="'assignments['+index+'][note]'" x-model="item.note" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Catatan khusus...">
                </div>
             </div>
         </div>
      </template>
      
      <div x-show="!orderId" class="col-span-1 md:col-span-2 text-center py-8 text-gray-500 border border-dashed rounded bg-gray-50">
          Silakan pilih order terlebih dahulu.
      </div>

    </div>

    <div class="pt-6 border-t flex justify-end space-x-3">
      <a href="{{ route('assignments.index') }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 font-medium my-auto text-sm">Batal</a>
      <x-primary-button type="submit" ::disabled="isLoading || !orderId">
          Buat Assignment
      </x-primary-button>
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
        hasPreassigned: false,

        getVehicleText(id) {
            const v = this.availableVehicles.find(x => x.id == id);
            return v ? v.text : '';
        },

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
                        const preassigned = data.preassigned_vehicle_ids || [];
                        this.hasPreassigned = preassigned.length > 0;
                        
                        this.items = Array.from({ length: this.vehicleCount }, (_, i) => ({
                            driver_id: '',
                            vehicle_id: preassigned[i] || '', // Auto-select if available
                            guide_id: '',
                            note: ''
                        }));
                    } else {
                         // Check if loaded data implies preassignment?
                         // If reusing old input, we might not know.
                         // But data.preassigned_vehicle_ids is truth.
                         const preassigned = data.preassigned_vehicle_ids || [];
                         this.hasPreassigned = preassigned.length > 0;
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
