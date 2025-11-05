{{-- resources/views/assignments/my.blade.php --}}
@extends('layouts.app')

@section('title', 'Tugas Saya')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-4xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Tugas Saya</h1>
  </div>

  <div class="space-y-3">
    @forelse($assignments as $a)
      @php
        // safe access helpers
        $order = $a->order ?? null;

        // pickup formatting
        $orderPickup = '-';
        if ($order && !empty($order->pickup_time)) {
          try {
            $orderPickup = \Carbon\Carbon::parse($order->pickup_time)->format('d M Y H:i');
          } catch (\Throwable $e) {
            $orderPickup = '-';
          }
        }

        // product name (safe)
        $productName = '-';
        if ($order && isset($order->product)) {
          // handle relationship object or array
          $productName = $order->product->name ?? (is_array($order->product) ? ($order->product['name'] ?? '-') : '-');
        } elseif (isset($order->product->name)) {
          $productName = $order->product->name ?? '-';
        }

        // driver payload
        $driverPayload = null;
        if (!empty($a->driver)) {
          $driverPayload = [
            'id' => $a->driver->id ?? null,
            'name' => $a->driver->name ?? null,
            'phone' => $a->driver->phone ?? null,
          ];
        }

        // guide payload
        $guidePayload = null;
        if (!empty($a->guide)) {
          $guidePayload = [
            'id' => $a->guide->id ?? null,
            'name' => $a->guide->name ?? null,
            'phone' => $a->guide->phone ?? null,
          ];
        }

        // prepare payload and JSON string safely
        $payload = [
          'id' => $a->id,
          'order' => [
            'customer' => $order->customer_name ?? '-',
            'pickup' => $orderPickup,
            'from' => $order->pickup_location ?? '-',
            'to' => $order->destination ?? '-',
            'product' => $productName ?? '-',
          ],
          'driver' => $driverPayload,
          'guide' => $guidePayload,
          'status' => $a->status ?? '-',
          'note' => $a->note ?? null,
        ];

        // json encode once here to avoid inline parsing issues
        $payloadJson = json_encode($payload);
      @endphp

      <div class="bg-white p-3 rounded shadow flex items-center justify-between">
        <div>
          <div class="font-medium">#{{ $a->id }} — {{ $order->customer_name ?? '-' }}</div>
          <div class="text-xs text-gray-500">
            {{ $orderPickup }} · {{ $productName ?? '-' }}
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-600">{{ ucfirst($a->status ?? '-') }}</span>

          {{-- Open modal with safe JSON payload (payloadJson already encoded) --}}
          <button
            onclick='openAssignmentModal({!! $payloadJson !!})'
            class="px-3 py-1 bg-indigo-600 text-white rounded text-sm"
            type="button"
          >
            Detail & Action
          </button>
        </div>
      </div>
    @empty
      <div class="bg-white p-4 rounded shadow text-center text-gray-500">Belum ada tugas.</div>
    @endforelse
  </div>

  {{-- pagination if present (controller should paginate) --}}
  @if(isset($assignments) && method_exists($assignments, 'links'))
    <div class="mt-4">
      {{ $assignments->appends(request()->query())->links() }}
    </div>
  @endif
</div>

{{-- Modal for assignment details --}}
<div
  x-data="assignmentModal()"
  x-init="init()"
  x-show="open"
  x-cloak
  class="fixed inset-0 z-50 flex items-center justify-center p-4"
  style="display: none;"
>
  {{-- backdrop --}}
  <div class="absolute inset-0 bg-black/40" @click="close()" aria-hidden="true"></div>

  {{-- panel --}}
  <div
    class="relative bg-white rounded shadow-lg max-w-2xl w-full z-50 p-4"
    x-transition
    @keydown.escape.window="close()"
    role="dialog"
    aria-modal="true"
    aria-label="Detail Assignment"
  >
    <div class="flex items-start justify-between">
      <h3 class="text-lg font-semibold">Detail Assignment — <span x-text="payload.id"></span></h3>
      <button @click="close()" class="text-gray-500 hover:text-gray-800" aria-label="Tutup">✕</button>
    </div>

    <div class="mt-3 text-sm space-y-2">
      <div><strong>Customer:</strong> <span x-text="payload.order && payload.order.customer ? payload.order.customer : '-'"></span></div>
      <div><strong>Pickup:</strong> <span x-text="payload.order && payload.order.pickup ? payload.order.pickup : '-'"></span></div>
      <div><strong>From → To:</strong> <span x-text="payload.order && payload.order.from ? payload.order.from : '-'"></span> → <span x-text="payload.order && payload.order.to ? payload.order.to : '-'"></span></div>
      <div><strong>Product:</strong> <span x-text="payload.order && payload.order.product ? payload.order.product : '-'"></span></div>
      <div><strong>Driver:</strong> <span x-text="payload.driver && payload.driver.name ? payload.driver.name : '-'"></span> (<span x-text="payload.driver && payload.driver.id ? payload.driver.id : '-'"></span>)</div>
      <div><strong>Guide:</strong> <span x-text="payload.guide && payload.guide.name ? payload.guide.name : '-'"></span> (<span x-text="payload.guide && payload.guide.id ? payload.guide.id : '-'"></span>)</div>
      <div><strong>Note:</strong> <span x-text="payload.note ? payload.note : '-'"></span></div>
      <div><strong>Status:</strong> <span x-text="payload.status ? payload.status : '-'"></span></div>
    </div>

    <div class="mt-4 flex items-center justify-end space-x-2">
      {{-- Actions for performer (driver/guide) --}}
      @auth
      <template x-if="isCurrentPerformer()">
        <div class="flex items-center space-x-2">
          {{-- Accept --}}
          <form x-bind:action="changeStatusUrl('accepted')" method="POST" x-ref="formAccept">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="status" value="accepted">
            <button type="button" @click="confirmAndSubmit($refs.formAccept)" class="px-3 py-2 bg-green-600 text-white rounded">Accept</button>
          </form>

          {{-- Decline --}}
          <form x-bind:action="changeStatusUrl('declined')" method="POST" x-ref="formDecline">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="status" value="declined">
            <button type="button" @click="confirmAndSubmit($refs.formDecline)" class="px-3 py-2 bg-red-600 text-white rounded">Decline</button>
          </form>

          {{-- Complete --}}
          <form x-bind:action="changeStatusUrl('completed')" method="POST" x-ref="formCompleted">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="status" value="completed">
            <button type="button" @click="confirmAndSubmit($refs.formCompleted)" class="px-3 py-2 bg-blue-600 text-white rounded">Complete</button>
          </form>
        </div>
      </template>
      @endauth

      <button @click="close()" class="px-3 py-2 bg-gray-200 rounded">Close</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
  // open modal from inline onclick
  function openAssignmentModal(payload) {
    const event = new CustomEvent('open-assignment-modal', { detail: payload });
    window.dispatchEvent(event);
  }

  function assignmentModal() {
    return {
      open: false,
      payload: {},

      // inject current user info safely
      currentUserId: {!! json_encode(auth()->check() ? auth()->id() : null) !!},
      currentUserRole: {!! json_encode(auth()->check() ? auth()->user()->role : null) !!},

      init() {
        window.addEventListener('open-assignment-modal', (e) => {
          this.payload = e.detail || {};
          this.open = true;
        });
      },

      close() {
        this.open = false;
        this.payload = {};
      },

      // check if current authenticated user is the driver or guide for this assignment
      isCurrentPerformer() {
        if (!this.currentUserId) return false;
        if (this.currentUserRole === 'driver' && this.payload.driver && this.payload.driver.id == this.currentUserId) return true;
        if (this.currentUserRole === 'guide' && this.payload.guide && this.payload.guide.id == this.currentUserId) return true;
        return false;
      },

      // returns URL for posting status change
      changeStatusUrl(status) {
        if (!this.payload || !this.payload.id) return '#';
        // matches route: POST /assignments/{assignment}/status
        return `/assignments/${this.payload.id}/status`;
      },

      confirmAndSubmit(formRef) {
        if (!confirm('Yakin ingin melakukan aksi ini?')) return;
        formRef.submit();
      }
    }
  }
</script>
@endpush

@endsection
