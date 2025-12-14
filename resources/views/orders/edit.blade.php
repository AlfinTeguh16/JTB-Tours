@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-4xl mx-auto p-6" 
     x-data="orderEditForm({ 
        products: {{ $products->toJson() }}, 
        currentProductId: {{ $order->product_id }},
        currentBranchId: {{ $order->product_branch_id ?? 'null' }},
        currentVehicleCount: {{ $order->vehicle_count ?? 1 }},
        currentDuration: {{ $order->estimated_duration_minutes ?? 0 }},
        adults: {{ $order->adults ?? 0 }},
        children: {{ $order->children ?? 0 }},
        babies: {{ $order->babies ?? 0 }}
     })">
     
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit Order #{{ $order->id }}</h1>
    <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Kembali</a>
  </div>

  <form action="{{ route('orders.update', $order->id) }}" method="POST" class="bg-white p-6 rounded-lg shadow-md space-y-6">
    @csrf
    @method('PUT')

    @if($errors->any())
      <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded">
        <ul class="list-disc pl-5">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <!-- Customer Info -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold border-b pb-2">Data Pelanggan</h3>
        <div>
           <label class="block text-sm font-medium text-gray-700">Nama Customer</label>
           <input type="text" name="customer_name" value="{{ old('customer_name', $order->customer_name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email', $order->email) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Telepon</label>
                <input type="text" name="phone" value="{{ old('phone', $order->phone) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
        </div>
      </div>

      <!-- Service Info -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold border-b pb-2">Layanan & Rute</h3>
        
        <!-- Product Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Pilih Layanan / Product</label>
            <select name="product_id" x-model="productId" @change="handleProductChange()" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Pilih Product --</option>
                <template x-for="p in products" :key="p.id">
                    <option :value="p.id" x-text="p.name" :selected="p.id == productId"></option>
                </template>
            </select>
        </div>

        <!-- Branch Selection (Dynamic) -->
        <div x-show="availableBranches.length > 0" x-transition>
            <label class="block text-sm font-medium text-gray-700">Pilih Rute / Cabang</label>
            <select name="product_branch_id" x-model="branchId" @change="handleBranchChange()"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Pilih Rute --</option>
                <template x-for="b in availableBranches" :key="b.id">
                    <option :value="b.id" x-text="b.name + ' (' + formatDuration(b.duration_minutes) + ')'"
                            :selected="b.id == branchId"></option>
                </template>
            </select>
        </div>
        
        <!-- Exclusive Benefits Info -->
        <div x-show="currentProduct && currentProduct.is_exclusive" x-transition class="bg-indigo-50 p-3 rounded border border-indigo-100 text-sm text-indigo-800">
            <strong>✨ Fasilitas Eksklusif:</strong>
            <ul class="list-disc pl-5 mt-1">
                <template x-if="currentProduct.snack"><li x-text="'Snack'"></li></template>
                <template x-if="currentProduct.water"><li x-text="'Air Mineral'"></li></template>
                <template x-if="currentProduct.magazine"><li x-text="'Majalah'"></li></template>
            </ul>
        </div>
      </div>

      <!-- Time & Location -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold border-b pb-2">Waktu & Lokasi</h3>
        
        <div>
           <label class="block text-sm font-medium text-gray-700">Waktu Penjemputan</label>
           <input type="datetime-local" name="pickup_time" x-model="pickupTime" @change="recalcArrival" required 
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        
        <div>
           <label class="block text-sm font-medium text-gray-700">Waktu Sampai (Opsional)</label>
           <input type="datetime-local" name="arrival_time" x-model="arrivalTime" @change="recalcDuration" 
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Estimasi Durasi (Menit)</label>
            <div class="flex items-center">
                <input type="number" name="estimated_duration_minutes" x-model="duration" @input="recalcArrival" min="1" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <span class="ml-2 text-sm text-gray-500" x-text="formatDuration(duration)"></span>
            </div>
            <p class="text-xs text-gray-500 mt-1">Otomatis dari Rute atau Waktu.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Lokasi Jemput</label>
            <input type="text" name="pickup_location" value="{{ old('pickup_location', $order->pickup_location) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Tujuan</label>
            <input type="text" name="destination" value="{{ old('destination', $order->destination) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
      </div>

      <!-- Passengers & Vehicle -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold border-b pb-2">Penumpang & Kendaraan</h3>
        
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700">Dewasa</label>
                <input type="number" name="adults" x-model.number="adults" @input="updatePassengers" min="0" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Anak</label>
                <input type="number" name="children" x-model.number="children" @input="updatePassengers" min="0" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Bayi</label>
                <input type="number" name="babies" x-model.number="babies" @input="updatePassengers" min="0" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
        </div>
        <input type="hidden" name="passengers" x-model="totalPassengers">



        <div>
            <label class="block text-sm font-medium text-gray-700">Jumlah Mobil Dibutuhkan</label>
            <input type="number" name="vehicle_count" x-model="vehicleCount" min="1" 
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm font-semibold text-blue-800">
            <p class="text-xs text-gray-500 mt-1">Dihitung otomatis (default 4 pax/mobil), silakan ubah jika perlu.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Catatan</label>
            <textarea name="note" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('note', $order->note) }}</textarea>
        </div>
      </div>

    </div>

    <div class="flex justify-end pt-6 border-t">
      <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md shadow hover:bg-blue-700 font-medium">Simpan Perubahan</button>
    </div>
  </form>
</div>

<script>
function orderEditForm(data) {
    return {
        products: data.products,
        productId: data.currentProductId,
        branchId: data.currentBranchId,
        
        pickupTime: '{{ old('pickup_time', $order->pickup_time ? $order->pickup_time->format('Y-m-d\TH:i') : '') }}',
        arrivalTime: '{{ old('arrival_time', $order->arrival_time ? $order->arrival_time->format('Y-m-d\TH:i') : '') }}',
        duration: data.currentDuration,
        
        adults: data.adults,
        children: data.children,
        babies: data.babies,
        totalPassengers: {{ $order->passengers ?? 1 }},
        vehicleCount: data.currentVehicleCount,

        get currentProduct() {
            return this.products.find(p => p.id == this.productId) || null;
        },

        get availableBranches() {
            const p = this.currentProduct;
            return p && p.branches ? p.branches : [];
        },
        
        get currentBranch() {
            if (!this.branchId) return null;
            return this.availableBranches.find(b => b.id == this.branchId) || null;
        },

        init() {
            this.updatePassengers();
            // Do not reset branch on init for edit mode
        },

        handleProductChange(resetBranch = true) {
            if (resetBranch) {
                this.branchId = '';
                this.duration = 0;
            }
            if (!this.duration && this.currentProduct) {
                // Fallback
                this.duration = Math.round(this.currentProduct.hour * 60);
            }
            this.recalcVehicleCount();
        },

        handleBranchChange() {
            if (this.currentBranch) {
                this.duration = this.currentBranch.duration_minutes;
                this.recalcArrival();
            }
        },

        updatePassengers() {
            this.totalPassengers = (parseInt(this.adults)||0) + (parseInt(this.children)||0) + (parseInt(this.babies)||0);
            this.recalcVehicleCount();
        },

        recalcVehicleCount() {
            let cap = 4; // Default
            if (this.currentProduct && this.currentProduct.capacity) {
                cap = this.currentProduct.capacity;
            }
            this.vehicleCount = Math.max(1, Math.ceil(this.totalPassengers / cap));
        },

        recalcArrival() { // Recalculate Arrival based on Pickup + Duration
             if (this.pickupTime && this.duration) {
                const start = new Date(this.pickupTime);
                const durationMs = parseInt(this.duration) * 60000;
                const end = new Date(start.getTime() + durationMs);
                
                // Format to YYYY-MM-DDTHH:mm
                const year = end.getFullYear();
                const month = String(end.getMonth() + 1).padStart(2, '0');
                const day = String(end.getDate()).padStart(2, '0');
                const hours = String(end.getHours()).padStart(2, '0');
                const minutes = String(end.getMinutes()).padStart(2, '0');
                
                this.arrivalTime = `${year}-${month}-${day}T${hours}:${minutes}`;
             }
        },

        recalcDuration() {
            if (this.pickupTime && this.arrivalTime) {
                const start = new Date(this.pickupTime);
                const end = new Date(this.arrivalTime);
                if (end > start) {
                    const diffMs = end - start;
                    const diffMins = Math.round(diffMs / 60000);
                    this.duration = diffMins;
                }
            }
        },
        
        formatDuration(mins) {
            if (!mins) return '-';
            const h = Math.floor(mins / 60);
            const m = mins % 60;
            return (h > 0 ? h + ' Jam ' : '') + (m > 0 ? m + ' Menit' : '');
        }
    }
}
</script>
@endsection