@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-7xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Orders</h1>
    <div class="flex items-center space-x-2">
      @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin']))
        <a href="{{ route('orders.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Buat Order</a>
      @endif

      <a href="{{ route('reports.export.excel', array_merge(request()->query(), ['type' => 'orders'])) }}" class="px-3 py-2 bg-gray-100 rounded">Export Excel</a>
      <a href="{{ route('reports.export.pdf', array_merge(request()->query(), ['type' => 'orders'])) }}" class="px-3 py-2 bg-gray-100 rounded">Export PDF</a>
    </div>
  </div>

  {{-- Filter --}}
  <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-6 gap-2">
    <div>
      <label class="block text-xs text-gray-600">Cari</label>
      <input name="q" value="{{ request('q') }}" placeholder="nama / telepon / produk" class="mt-1 block w-full rounded border-gray-200" />
    </div>

    <div>
      <label class="block text-xs text-gray-600">Product</label>
      <select name="product_id" class="mt-1 block w-full rounded border-gray-200">
        <option value="">Semua</option>
        @foreach($products ?? [] as $p)
          <option value="{{ $p->id }}" @if(request('product_id') == $p->id) selected @endif>{{ $p->name }}</option>
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

    <div class="flex items-end">
      <button class="px-3 py-2 bg-gray-800 text-white rounded">Filter</button>
      <a href="{{ route('orders.index') }}" class="ml-2 px-3 py-2 bg-gray-200 rounded">Reset</a>
    </div>
  </form>

  {{-- Table --}}
  <div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium">#</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Customer</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Pickup / Arrival</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Product</th>
          <th class="px-4 py-2 text-left text-sm font-medium">People</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Status</th>
          <th class="px-4 py-2 text-right text-sm font-medium">Aksi</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100">
        @forelse($orders as $o)
          <tr>
            <td class="px-4 py-3 text-sm">{{ $o->id }}</td>
            <td class="px-4 py-3 text-sm">
              <div class="font-medium">{{ $o->customer_name }}</div>
              <div class="text-xs text-gray-500">{{ $o->summary_contact ?? ($o->email ?? '-') }}</div>
            </td>
            <td class="px-4 py-3 text-sm">
              <div>{{ $o->formatted_pickup ?? '-' }}</div>
              <div class="text-xs text-gray-500">{{ $o->formatted_arrival ?? '-' }}</div>
            </td>
            <td class="px-4 py-3 text-sm">{{ optional($o->product)->name ?? '-' }}</td>
            <td class="px-4 py-3 text-sm">
              {{ $o->summary_people ?? (($o->adults ?? 0) . ' adults · ' . ($o->children ?? 0) . ' children · ' . ($o->babies ?? 0) . ' babies') }}
            </td>
            <td class="px-4 py-3 text-sm">
              <span class="px-2 py-1 rounded text-xs {{ $o->status == 'completed' ? 'bg-blue-100 text-blue-800' : ($o->status=='assigned' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') }}">
                {{ ucfirst($o->status ?? 'pending') }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-right">

              {{-- prepare payload safely in PHP then output as base64 JSON to avoid analyzer issues --}}
              @php
                $payload = [
                  'id' => $o->id,
                  'customer' => $o->customer_name,
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

    <div class="p-4">
      {{ $orders->links() }}
    </div>
  </div>
</div>

{{-- Order modal --}}
<div x-data="orderModal()" x-init="init()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded shadow-lg max-w-2xl w-full p-4 z-50">
    <div class="flex items-start justify-between">
      <h3 class="text-lg font-medium">Order #<span x-text="payload.id"></span></h3>
      <button @click="close()" class="text-gray-500">✕</button>
    </div>

    <div class="mt-3 space-y-2 text-sm">
      <div><strong>Customer:</strong> <span x-text="payload.customer"></span></div>
      <div><strong>Email:</strong> <span x-text="payload.email"></span></div>
      <div><strong>Phone:</strong> <span x-text="payload.phone"></span></div>
      <div><strong>Pickup:</strong> <span x-text="payload.pickup"></span></div>
      <div><strong>Arrival:</strong> <span x-text="payload.arrival"></span></div>
      <div><strong>From → To:</strong> <span x-text="payload.from"></span> → <span x-text="payload.to"></span></div>
      <div><strong>Product:</strong> <span x-text="payload.product"></span></div>
      <div><strong>People:</strong> <span x-text="payload.adults"></span> adults · <span x-text="payload.children"></span> children · <span x-text="payload.babies"></span> babies</div>
      <div><strong>Note:</strong> <span x-text="payload.note || '-'"></span></div>
      <div><strong>Status:</strong> <span x-text="payload.status"></span></div>
    </div>

    <div class="mt-4 flex items-center justify-end space-x-2">
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

  // Open modal by passing the button element. Supports base64-encoded payload to avoid quoting issues.
  function openOrderModal(el) {
    try {
      let raw = null;
      if (el instanceof Element) {
        raw = el.getAttribute('data-payload-b64') || el.getAttribute('data-payload');
        if (!raw) return;
        // if base64, decode
        try {
          // detect base64 by trying to decode; if fails, fall back to raw
          const decoded = atob(raw);
          const payload = JSON.parse(decoded);
          window.dispatchEvent(new CustomEvent('open-order-modal', { detail: payload }));
          return;
        } catch (err) {
          // not base64 (or decode failed), try parse raw
        }

        const payload = JSON.parse(raw);
        window.dispatchEvent(new CustomEvent('open-order-modal', { detail: payload }));
      } else if (typeof el === 'string') {
        const payload = JSON.parse(el);
        window.dispatchEvent(new CustomEvent('open-order-modal', { detail: payload }));
      }
    } catch (err) {
      console.error('openOrderModal error', err);
    }
  }
</script>
@endpush

@endsection
