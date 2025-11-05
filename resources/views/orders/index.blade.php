@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-7xl mx-auto p-4">
  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
    <h1 class="text-2xl font-semibold">Orders</h1>
    <div class="flex flex-wrap gap-2">
      @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin']))
        <a href="{{ route('orders.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded text-sm whitespace-nowrap">Buat Order</a>
      @endif

      <a href="{{ route('reports.export.excel', array_merge(request()->query(), ['type' => 'orders'])) }}" class="px-3 py-2 bg-gray-100 rounded text-sm whitespace-nowrap">Export Excel</a>
      <a href="{{ route('reports.export.pdf', array_merge(request()->query(), ['type' => 'orders'])) }}" class="px-3 py-2 bg-gray-100 rounded text-sm whitespace-nowrap">Export PDF</a>
    </div>
  </div>

  {{-- Filter --}}
  <form method="GET" class="mb-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
    <div>
      <label class="block text-xs text-gray-600">Cari</label>
      <input name="q" value="{{ request('q') }}" placeholder="nama / telepon / produk" class="mt-1 block w-full rounded border-gray-200" />
    </div>

    <div>
      <label class="block text-xs text-gray-600">Product</label>
      <select name="product_id" class="mt-1 block w-full rounded border-gray-200">
        <option value="">Semua</option>
        @foreach(($products ?? collect()) as $p)
          @if(is_object($p))
            <option value="{{ $p->id }}" @if(request('product_id') == $p->id) selected @endif>
              {{ $p->name ?? 'Unknown Product' }}
            </option>
          @endif
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-xs text-gray-600">Status</label>
      <select name="status" class="mt-1 block w-full rounded border-gray-200">
        <option value="">Semua</option>
        <option value="pending" @if(request('status')=='pending') selected @endif>Pending</option>
        <option value="assigned" @if(request('status')=='assigned') selected @endif>Assigned</option>
        <option value="completed" @if(request('status')=='completed') selected @endif>Completed</option>
      </select>
    </div>

    <div>
      <label class="block text-xs text-gray-600">From</label>
      <input type="date" name="from" value="{{ request('from') }}" class="mt-1 block w-full rounded border-gray-200" />
    </div>

    <div>
      <label class="block text-xs text-gray-600">To</label>
      <input type="date" name="to" value="{{ request('to') }}" class="mt-1 block w-full rounded border-gray-200" />
    </div>

    <div class="flex items-end gap-2">
      <button class="px-3 py-2 bg-gray-800 text-white rounded text-sm w-full sm:w-auto">Filter</button>
      <a href="{{ route('orders.index') }}" class="px-3 py-2 bg-gray-200 rounded text-sm w-full sm:w-auto text-center">Reset</a>
    </div>
  </form>

  {{-- Table --}}
  <div class="bg-white rounded shadow overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-50 text-gray-700 text-xs uppercase tracking-wider">
        <tr>
          <th class="px-4 py-2 text-left">#</th>
          <th class="px-4 py-2 text-left">Customer</th>
          <th class="px-4 py-2 text-left">Pickup / Arrival</th>
          <th class="px-4 py-2 text-left">Product</th>
          <th class="px-4 py-2 text-left">People</th>
          <th class="px-4 py-2 text-left">Status</th>
          <th class="px-4 py-2 text-right">Aksi</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100">
        @forelse($orders as $o)
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3">{{ $o->id }}</td>
            <td class="px-4 py-3">
              <div class="font-medium">{{ $o->customer_name ?? '-' }}</div>
              <div class="text-xs text-gray-500">{{ $o->summary_contact ?? '-' }}</div>
            </td>
            <td class="px-4 py-3">
              <div>{{ $o->formatted_pickup ?? '-' }}</div>
              <div class="text-xs text-gray-500">{{ $o->formatted_arrival ?? '-' }}</div>
            </td>
            <td class="px-4 py-3">{{ optional($o->product)->name ?? '-' }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ $o->summary_people ?? '-' }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 rounded text-xs font-medium
                {{ $o->status == 'completed' ? 'bg-blue-100 text-blue-800' :
                   ($o->status=='assigned' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') }}">
                {{ ucfirst($o->status ?? 'pending') }}
              </span>
            </td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              @php
                $payload = [
                  'id' => $o->id,
                  'customer' => $o->customer_name ?? '-',
                  'email' => $o->email ?? '-',
                  'phone' => $o->phone ?? '-',
                  'pickup' => $o->formatted_pickup ?? '-',
                  'arrival' => $o->formatted_arrival ?? '-',
                  'from' => $o->pickup_location ?? '-',
                  'to' => $o->destination ?? '-',
                  'product' => optional($o->product)->name ?? '-',
                  'adults' => $o->adults ?? 0,
                  'children' => $o->children ?? 0,
                  'babies' => $o->babies ?? 0,
                  'note' => $o->note ?? '-',
                  'status' => $o->status ?? 'pending',
                ];
                $payload_b64 = base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
              @endphp

              <button type="button"
                data-payload-b64="{{ $payload_b64 }}"
                onclick="openOrderModal(this)"
                class="px-2 py-1 bg-indigo-600 text-white rounded text-xs">Detail</button>

              @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin']))
                <a href="{{ route('orders.edit', $o) }}" class="px-2 py-1 ml-1 bg-yellow-400 text-white rounded text-xs">Edit</a>

                <form action="{{ route('orders.destroy', $o) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus order #{{ $o->id }}?')">
                  @csrf @method('DELETE')
                  <button class="px-2 py-1 ml-1 bg-red-600 text-white rounded text-xs">Hapus</button>
                </form>

                <a href="{{ route('assignments.create', ['order' => $o->id]) }}" class="px-2 py-1 ml-1 bg-green-600 text-white rounded text-xs">Assign</a>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="p-4 text-center text-gray-500">Belum ada order.</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">{{ $orders->links() }}</div>
  </div>
</div>

{{-- Modal --}}
<div x-data="orderModal()" x-init="init()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded shadow-lg max-w-2xl w-full p-4 z-50 overflow-y-auto max-h-[90vh]">
    <div class="flex items-start justify-between">
      <h3 class="text-lg font-medium">Order #<span x-text="payload.id"></span></h3>
      <button @click="close()" class="text-gray-500">✕</button>
    </div>

    <div class="mt-3 space-y-2 text-sm">
      <template x-for="(value, key) in payload" :key="key">
        <div><strong x-text="key.charAt(0).toUpperCase() + key.slice(1) + ':'"></strong> <span x-text="value"></span></div>
      </template>
    </div>

    <div class="mt-4 flex items-center justify-end">
      <button @click="close()" class="px-3 py-2 bg-gray-200 rounded">Close</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function orderModal() {
    return {
      open: false,
      payload: {},
      init() {
        window.addEventListener('open-order-modal', (e) => {
          this.payload = e.detail;
          this.open = true;
        });
      },
      close() { this.open = false; this.payload = {}; }
    }
  }

  function openOrderModal(el) {
    try {
      const raw = el.getAttribute('data-payload-b64');
      const payload = JSON.parse(atob(raw));
      window.dispatchEvent(new CustomEvent('open-order-modal', { detail: payload }));
    } catch (err) {
      console.error('openOrderModal error', err);
    }
  }
</script>
@endpush
@endsection
