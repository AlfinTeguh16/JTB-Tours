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

  <form id="orderForm" action="{{ route('orders.update',$order->id) }}" method="POST" class="bg-white p-6 rounded shadow">
    @csrf @method('PUT')

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block mb-2">Nama Customer</label>
        <input id="customer_name" type="text" name="customer_name" value="{{ old('customer_name',$order->customer_name) }}" class="w-full border p-2 rounded" required>
      </div>

      <div>
        <label class="block mb-2">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email',$order->email) }}" class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block mb-2">Telepon</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone',$order->phone) }}" class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block mb-2">Jumlah Orang (total)</label>
        <input id="passengers" type="number" name="passengers" value="{{ old('passengers',$order->passengers) }}" min="1" class="w-full border p-2 rounded" required>
      </div>

      <div>
        <label class="block mb-2">Dewasa</label>
        <input id="adults" type="number" name="adults" value="{{ old('adults',$order->adults) }}" min="0" class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block mb-2">Anak-anak</label>
        <input id="children" type="number" name="children" value="{{ old('children',$order->children) }}" min="0" class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block mb-2">Bayi</label>
        <input id="babies" type="number" name="babies" value="{{ old('babies',$order->babies) }}" min="0" class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block mb-2">Jumlah Mobil</label>
        <input id="vehicle_count" type="number" name="vehicle_count" value="{{ old('vehicle_count',$order->vehicle_count) }}" min="1" class="w-full border p-2 rounded">
      </div>

      <div class="col-span-2">
        <label class="block mb-2">Product</label>
        <select id="productSelect" name="product_id" class="w-full border p-2 rounded" required>
          <option value="">-- Pilih Product --</option>
          @foreach($products as $p)
            <option value="{{ $p->id }}"
              {{ (old('product_id',$order->product_id) == $p->id) ? 'selected' : '' }}
              data-hour="{{ $p->hour ?? 0 }}"
              data-capacity="{{ $p->capacity ?? 0 }}"
            >{{ $p->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block mb-2">Waktu Penjemputan</label>
        <input id="pickup_time" type="datetime-local" name="pickup_time"
               value="{{ old('pickup_time', $order->pickup_time ? $order->pickup_time->format('Y-m-d\TH:i') : '') }}"
               class="w-full border p-2 rounded" required>
      </div>

      <div>
        <label class="block mb-2">Waktu Sampai (opsional)</label>
        <input id="arrival_time" type="datetime-local" name="arrival_time"
               value="{{ old('arrival_time', $order->arrival_time ? $order->arrival_time->format('Y-m-d\TH:i') : '') }}"
               class="w-full border p-2 rounded">
      </div>

      <div class="col-span-2">
        <label class="block mb-2">Estimasi Durasi (menit)</label>
        <input id="estimated_duration_minutes" type="number" name="estimated_duration_minutes" value="{{ old('estimated_duration_minutes',$order->estimated_duration_minutes) }}" min="1" class="w-full border p-2 rounded">
        <div class="text-xs text-gray-500 mt-1">Nilai akan otomatis diperbarui berdasarkan waktu dan product (hour × 60).</div>
      </div>

      <div class="col-span-2">
        <label class="block mb-2">Tempat Penjemputan</label>
        <input id="pickup_location" type="text" name="pickup_location" value="{{ old('pickup_location',$order->pickup_location) }}" class="w-full border p-2 rounded">
      </div>

      <div class="col-span-2">
        <label class="block mb-2">Tempat Tujuan</label>
        <input id="destination" type="text" name="destination" value="{{ old('destination',$order->destination) }}" class="w-full border p-2 rounded">
      </div>

      <div class="col-span-2">
        <label class="block mb-2">Catatan</label>
        <textarea id="note" name="note" rows="3" class="w-full border p-2 rounded">{{ old('note',$order->note) }}</textarea>
      </div>

      <div class="col-span-2">
        <div id="capacityInfo" class="text-sm text-gray-600">Capacity product: <span id="capValue">-</span></div>
        <div id="capacityWarning" class="mt-2 p-2 bg-yellow-50 text-yellow-800 rounded hidden"></div>
      </div>
    </div>

    <div class="mt-4 flex items-center space-x-2">
      <button id="saveBtn" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan Perubahan</button>
      <a href="{{ route('orders.index') }}" class="px-4 py-2 border rounded">Batal</a>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
  (function () {
    const adultsEl = document.getElementById('adults');
    const childrenEl = document.getElementById('children');
    const babiesEl = document.getElementById('babies');
    const passengersEl = document.getElementById('passengers');

    const pickupEl = document.getElementById('pickup_time');
    const arrivalEl = document.getElementById('arrival_time');
    const durationEl = document.getElementById('estimated_duration_minutes');

    const productSelect = document.getElementById('productSelect');

    const capValueEl = document.getElementById('capValue');
    const capacityWarningEl = document.getElementById('capacityWarning');

    const form = document.getElementById('orderForm');

    const productHours = @json($products->pluck('hour','id')) || {};
    const productCaps = @json($products->pluck('capacity','id')) || {};

    function parseDateTimeLocal(value) {
      if (!value) return null;
      const d = new Date(value);
      if (isNaN(d.getTime())) return null;
      return d;
    }

    function updatePassengers() {
      const adults = parseInt(adultsEl.value, 10) || 0;
      const children = parseInt(childrenEl.value, 10) || 0;
      const babies = parseInt(babiesEl.value, 10) || 0;
      const total = adults + children + babies;
      passengersEl.value = total;
      return total;
    }

    function computeBaseDurationMinutes() {
      const pickup = parseDateTimeLocal(pickupEl.value);
      const arrival = parseDateTimeLocal(arrivalEl.value);
      if (pickup && arrival) {
        const diffMs = arrival.getTime() - pickup.getTime();
        const diffMin = Math.round(diffMs / 60000);
        return diffMin >= 0 ? diffMin : null;
      }
      const storedBase = durationEl.dataset.baseMinutes;
      if (storedBase) return parseInt(storedBase,10);
      const current = parseInt(durationEl.value,10);
      return isNaN(current) ? null : current;
    }

    function updateDurationFromTimes() {
      const base = computeBaseDurationMinutes();
      if (base !== null) {
        durationEl.dataset.baseMinutes = base;
        const added = parseInt(durationEl.dataset.addedMinutes || '0', 10) || 0;
        durationEl.value = Math.max(0, base + added);
      } else {
        delete durationEl.dataset.baseMinutes;
      }
    }

    function applyCapacityLimitsForProduct(productId) {
      const cap = productId ? parseInt(productCaps[productId] ?? productSelect.querySelector(`option[value="${productId}"]`)?.dataset?.capacity ?? 0, 10) : 0;
      if (cap && cap > 0) {
        capValueEl.textContent = cap;
        adultsEl.max = cap;
        childrenEl.max = cap;
        babiesEl.max = cap;
      } else {
        capValueEl.textContent = '-';
        adultsEl.removeAttribute('max');
        childrenEl.removeAttribute('max');
        babiesEl.removeAttribute('max');
      }

      const adultsVal = parseInt(adultsEl.value,10) || 0;
      const childrenVal = parseInt(childrenEl.value,10) || 0;
      const babiesVal = parseInt(babiesEl.value,10) || 0;
      let total = adultsVal + childrenVal + babiesVal;

      if (cap && total > cap) {
        let excess = total - cap;
        const reducibleChildren = Math.min(childrenVal, excess);
        if (reducibleChildren > 0) {
          childrenEl.value = Math.max(0, childrenVal - reducibleChildren);
          excess -= reducibleChildren;
        }
        if (excess > 0) {
          const reducibleBabies = Math.min(babiesVal, excess);
          if (reducibleBabies > 0) {
            babiesEl.value = Math.max(0, babiesVal - reducibleBabies);
            excess -= reducibleBabies;
          }
        }
        if (excess > 0) {
          adultsEl.value = Math.max(0, adultsVal - excess);
          excess = 0;
        }
        showCapacityWarning('Jumlah penumpang melebihi kapasitas product, nilai telah disesuaikan.');
      } else {
        hideCapacityWarning();
      }

      updatePassengers();
    }

    function showCapacityWarning(msg) {
      capacityWarningEl.textContent = msg;
      capacityWarningEl.classList.remove('hidden');
    }
    function hideCapacityWarning() {
      capacityWarningEl.textContent = '';
      capacityWarningEl.classList.add('hidden');
    }

    function handleProductChange() {
      const selectedId = productSelect.value;
      let hour = 0;
      if (selectedId) {
        hour = parseFloat(productHours[selectedId] ?? productSelect.querySelector(`option[value="${selectedId}"]`)?.dataset?.hour ?? 0) || 0;
      }
      const newAdded = Math.round(hour * 60);

      const prevAdded = parseInt(durationEl.dataset.addedMinutes || '0',10) || 0;

      let base = null;
      if (typeof durationEl.dataset.baseMinutes !== 'undefined') {
        base = parseInt(durationEl.dataset.baseMinutes || '0',10);
      } else {
        const current = parseInt(durationEl.value,10);
        if (!isNaN(current)) {
          base = current - prevAdded;
          if (base < 0) base = 0;
          durationEl.dataset.baseMinutes = base;
        }
      }

      if (base === null || isNaN(base)) {
        base = 0;
        durationEl.dataset.baseMinutes = base;
      }

      const total = Math.max(0, base + newAdded);
      durationEl.value = total;

      durationEl.dataset.addedMinutes = newAdded;
      durationEl.dataset.addedProductId = selectedId || '';

      applyCapacityLimitsForProduct(selectedId);
    }

    function updateDuration() {
      const pickup = parseDateTimeLocal(pickupEl.value);
      const arrival = parseDateTimeLocal(arrivalEl.value);

      if (pickup && arrival) {
        const diffMs = arrival.getTime() - pickup.getTime();
        const diffMin = Math.round(diffMs / 60000);
        if (diffMin >= 0) {
          durationEl.dataset.baseMinutes = diffMin;
        } else {
          delete durationEl.dataset.baseMinutes;
        }
      }

      const added = parseInt(durationEl.dataset.addedMinutes || '0',10) || 0;
      const baseVal = parseInt(durationEl.dataset.baseMinutes || '0',10) || 0;
      durationEl.value = Math.max(0, baseVal + added);
    }

    [adultsEl, childrenEl, babiesEl].forEach(el => {
      if (!el) return;
      el.addEventListener('input', function () {
        const maxAttr = el.getAttribute('max');
        if (maxAttr) {
          const maxVal = parseInt(maxAttr, 10);
          const cur = parseInt(el.value, 10) || 0;
          if (cur > maxVal) {
            el.value = maxVal;
            showCapacityWarning('Nilai telah dibatasi sesuai kapasitas product.');
          } else {
            const total = updatePassengers();
            const cap = parseInt(productCaps[productSelect.value] ?? 0,10) || 0;
            if (cap && total > cap) {
              showCapacityWarning('Jumlah penumpang melebihi kapasitas product.');
            } else {
              hideCapacityWarning();
            }
          }
        } else {
          hideCapacityWarning();
        }
        updatePassengers();
      });
    });

    [pickupEl, arrivalEl].forEach(el => {
      if (!el) return;
      el.addEventListener('change', updateDuration);
      el.addEventListener('input', updateDuration);
    });

    if (productSelect) {
      productSelect.addEventListener('change', handleProductChange);
    }

    document.addEventListener('DOMContentLoaded', function () {
      updatePassengers();
      updateDuration();
      if (productSelect && productSelect.value) {
        applyCapacityLimitsForProduct(productSelect.value);
      } else {
        capValueEl.textContent = '-';
      }
      if (productSelect) handleProductChange();
    });

    form.addEventListener('submit', function (e) {
      updatePassengers();
      updateDuration();

      const passengersVal = parseInt(passengersEl.value,10) || 0;
      if (passengersVal <= 0) {
        e.preventDefault();
        alert('Jumlah penumpang harus lebih dari 0.');
        return false;
      }

      const cap = parseInt(productCaps[productSelect.value] ?? 0,10) || 0;
      if (cap && passengersVal > cap) {
        e.preventDefault();
        alert('Total penumpang melebihi kapasitas product. Sesuaikan jumlah penumpang atau pilih product lain.');
        return false;
      }

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
    });

  })();
</script>
@endpush