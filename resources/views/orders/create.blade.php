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
        <select id="productSelect" name="product_id" class="mt-1 block w-full rounded border-gray-200">
          <option value="">-- pilih product --</option>
          @foreach($products as $p)
            <option value="{{ $p->id }}" @if(old('product_id') == $p->id) selected @endif data-hour="{{ $p->hour ?? 0 }}" data-capacity="{{ $p->capacity ?? 0 }}">{{ $p->name }}</option>
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
        <div class="text-xs text-gray-500 mt-1">Diisi otomatis dari waktu penjemputan & waktu sampai, ditambah durasi product (hour × 60). Anda bisa edit jika perlu.</div>
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
        <div id="capacityInfo" class="text-sm text-gray-600">Capacity product: <span id="capValue">-</span></div>
        <div id="capacityWarning" class="mt-1 p-2 bg-yellow-50 text-yellow-800 rounded hidden"></div>
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

    <input id="passengers" type="hidden" name="passengers" value="{{ old('passengers', (old('adults',1) + old('children',0) + old('babies',0)) ) }}" />

    <div class="mt-4 flex items-center space-x-2">
      <button id="submitBtn" class="px-4 py-2 bg-blue-600 text-white rounded">Buat Order</button>
      <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-gray-200 rounded">Batal</a>
    </div>
  </form>
</div>


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
    const capacityInfoEl = document.getElementById('capacityInfo');

    const form = document.getElementById('orderForm');
    const submitBtn = document.getElementById('submitBtn');

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
      if (storedBase) return parseInt(storedBase, 10);
      const current = parseInt(durationEl.value, 10);
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

    function handleProductChange() {
      const selectedId = productSelect.value;
      let hour = 0;
      if (selectedId) {
        hour = parseFloat(productHours[selectedId] ?? productSelect.querySelector(`option[value="${selectedId}"]`)?.dataset?.hour ?? 0) || 0;
      }
      const newAdded = Math.round(hour * 60);

      const prevAdded = parseInt(durationEl.dataset.addedMinutes || '0', 10) || 0;

      let base = null;
      if (typeof durationEl.dataset.baseMinutes !== 'undefined') {
        base = parseInt(durationEl.dataset.baseMinutes || '0', 10);
      } else {
        const current = parseInt(durationEl.value, 10);
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

      const adultsVal = parseInt(adultsEl.value, 10) || 0;
      const childrenVal = parseInt(childrenEl.value, 10) || 0;
      const babiesVal = parseInt(babiesEl.value, 10) || 0;
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

        showCapacityWarning('Jumlah penumpang melebihi kapasitas product, kami menyesuaikan nilai agar sesuai kapasitas.');
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
          durationEl.value = '';
          return;
        }
      }

      const added = parseInt(durationEl.dataset.addedMinutes || '0', 10) || 0;
      const baseVal = parseInt(durationEl.dataset.baseMinutes || '0', 10) || 0;
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
            const cap = parseInt(productCaps[productSelect.value] ?? 0, 10) || 0;
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
      productSelect.addEventListener('change', function () {
        handleProductChange();
      });
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

      const passengersVal = parseInt(passengersEl.value, 10) || 0;
      if (passengersVal <= 0) {
        e.preventDefault();
        alert('Jumlah penumpang harus lebih dari 0.');
        return false;
      }

      const cap = parseInt(productCaps[productSelect.value] ?? 0, 10) || 0;
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

@endsection