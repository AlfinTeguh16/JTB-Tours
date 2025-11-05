@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-3xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Buat Order</h1>
    <a href="{{ route('orders.index') }}" class="px-3 py-2 bg-gray-200 rounded">Kembali</a>
  </div>

  <form id="orderForm" action="{{ route('orders.store') }}" method="POST" class="bg-white p-4 rounded shadow">
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
        <input id="pickup_time" name="pickup_time" value="{{ old('pickup_time') }}" type="datetime-local" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Waktu Sampai (opsional)</label>
        <input id="arrival_time" name="arrival_time" value="{{ old('arrival_time') }}" type="datetime-local" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Estimasi Durasi (menit)</label>
        <input id="estimated_duration_minutes" name="estimated_duration_minutes" value="{{ old('estimated_duration_minutes') }}" type="number" min="0" class="mt-1 block w-full rounded border-gray-200" readonly />
        <div class="text-xs text-gray-500 mt-1">Diisi otomatis dari waktu penjemputan & waktu sampai. Anda bisa edit jika perlu.</div>
      </div>

      <div>
        <label class="block text-sm">Jumlah Mobil (opsional)</label>
        <input name="vehicle_count" value="{{ old('vehicle_count',1) }}" type="number" min="1" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Jumlah Dewasa</label>
        <input id="adults" name="adults" value="{{ old('adults',1) }}" type="number" min="0" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Jumlah Anak-anak</label>
        <input id="children" name="children" value="{{ old('children',0) }}" type="number" min="0" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Jumlah Bayi</label>
        <input id="babies" name="babies" value="{{ old('babies',0) }}" type="number" min="0" class="mt-1 block w-full rounded border-gray-200" />
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

    {{-- Hidden field: passengers (required oleh validator) --}}
    <input id="passengers" type="hidden" name="passengers" value="{{ old('passengers', (old('adults',1) + old('children',0) + old('babies',0)) ) }}" />

    <div class="mt-4 flex items-center space-x-2">
      <button id="submitBtn" class="px-4 py-2 bg-blue-600 text-white rounded">Buat Order</button>
      <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-gray-200 rounded">Batal</a>
    </div>
  </form>
</div>

@push('scripts')
<script>
  (function () {
    // Elements
    const adultsEl = document.getElementById('adults');
    const childrenEl = document.getElementById('children');
    const babiesEl = document.getElementById('babies');
    const passengersEl = document.getElementById('passengers');

    const pickupEl = document.getElementById('pickup_time');
    const arrivalEl = document.getElementById('arrival_time');
    const durationEl = document.getElementById('estimated_duration_minutes');

    const form = document.getElementById('orderForm');
    const submitBtn = document.getElementById('submitBtn');

    // Helper: parse datetime-local value into Date (local)
    function parseDateTimeLocal(value) {
      if (!value) return null;
      // value like "2025-11-03T14:30"
      const d = new Date(value);
      if (isNaN(d.getTime())) return null;
      return d;
    }

    // update passengers hidden field
    function updatePassengers() {
      const adults = parseInt(adultsEl.value, 10) || 0;
      const children = parseInt(childrenEl.value, 10) || 0;
      const babies = parseInt(babiesEl.value, 10) || 0;
      const total = adults + children + babies;
      passengersEl.value = total;
      return total;
    }

    // update duration from pickup & arrival (in minutes)
    function updateDuration() {
      const pickup = parseDateTimeLocal(pickupEl.value);
      const arrival = parseDateTimeLocal(arrivalEl.value);

      if (pickup && arrival) {
        const diffMs = arrival.getTime() - pickup.getTime();
        const diffMin = Math.round(diffMs / 60000);
        // If arrival earlier than pickup, set to 0 or leave blank (choose blank)
        if (diffMin >= 0) {
          durationEl.value = diffMin;
        } else {
          // jika arrival < pickup, kosongkan dan beri peringatan kecil
          durationEl.value = '';
        }
      } else {
        // jika belum lengkap, biarkan apa adanya (jika server sebelumnya mengisi, tetap)
        // opsi: kosongkan jika salah satu null
        durationEl.value = durationEl.value || '';
      }
    }

    // Attach listeners
    [adultsEl, childrenEl, babiesEl].forEach(el => {
      if (!el) return;
      el.addEventListener('input', updatePassengers);
    });

    [pickupEl, arrivalEl].forEach(el => {
      if (!el) return;
      el.addEventListener('change', updateDuration);
      // also update on input for browsers
      el.addEventListener('input', updateDuration);
    });

    // Ensure one-time update on page load to populate hidden fields with old() values
    document.addEventListener('DOMContentLoaded', function () {
      updatePassengers();
      updateDuration();
    });

    // Before submit, ensure passengers & duration are up-to-date
    form.addEventListener('submit', function (e) {
      updatePassengers();
      updateDuration();

      // If passengers is zero, prevent submit and show alert (server expects >=1 maybe)
      const passengersVal = parseInt(passengersEl.value, 10) || 0;
      if (passengersVal <= 0) {
        e.preventDefault();
        alert('Jumlah penumpang harus lebih dari 0.');
        return false;
      }

      // If pickup_time is set but duration empty and arrival exists earlier than pickup, warn user
      const pickup = parseDateTimeLocal(pickupEl.value);
      const arrival = parseDateTimeLocal(arrivalEl.value);
      if (pickup && arrival) {
        const diffMin = Math.round((arrival.getTime() - pickup.getTime()) / 60000);
        if (diffMin < 0) {
          e.preventDefault();
          alert('Waktu sampai lebih awal dari waktu penjemputan. Periksa input waktu.');
          return false;
        }
      }

      // allow submit
    });

  })();
</script>
@endpush

@endsection
