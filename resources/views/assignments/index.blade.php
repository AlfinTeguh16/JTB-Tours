@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-6xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Assignments</h1>
    <div class="space-x-2">
      <a href="{{ route('assignments.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Buat Assignment</a>
    </div>
  </div>

  {{-- Filter singkat --}}
  <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2">
    <div>
      <label class="block text-sm">Status</label>
      <select name="status" class="mt-1 block w-full rounded border-gray-200">
        <option value="">Semua</option>
        <option value="pending" @if(request('status')=='pending') selected @endif>Pending</option>
        <option value="accepted" @if(request('status')=='accepted') selected @endif>Accepted</option>
        <option value="declined" @if(request('status')=='declined') selected @endif>Declined</option>
        <option value="completed" @if(request('status')=='completed') selected @endif>Completed</option>
      </select>
    </div>

    <div>
      <label class="block text-sm">Driver</label>
      <input name="driver_id" value="{{ request('driver_id') }}" placeholder="driver id" class="mt-1 block w-full rounded border-gray-200" />
    </div>

    <div>
      <label class="block text-sm">From</label>
      <input name="from" type="date" value="{{ request('from') }}" class="mt-1 block w-full rounded border-gray-200" />
    </div>

    <div class="flex items-end">
      <button type="submit" class="px-3 py-2 bg-gray-800 text-white rounded">Filter</button>
      <a href="{{ route('assignments.index') }}" class="ml-2 px-3 py-2 bg-gray-200 rounded">Reset</a>
    </div>
  </form>

  {{-- Table --}}
  <div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium">ID</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Order</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Driver / Guide</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Assigned At</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Status</th>
          <th class="px-4 py-2 text-right text-sm font-medium">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($assignments as $a)
          <tr>
            <td class="px-4 py-3 text-sm">{{ $a->id }}</td>
            <td class="px-4 py-3 text-sm">
              <div class="font-medium">{{ $a->order->customer_name ?? '—' }}</div>
              <div class="text-xs text-gray-500">{{ $a->order->pickup_time ? \Carbon\Carbon::parse($a->order->pickup_time)->format('d M Y H:i') : '-' }}</div>
            </td>
            <td class="px-4 py-3 text-sm">
              <div>{{ $a->driver->name ?? '-' }} <span class="text-xs text-gray-400">({{ $a->driver_id }})</span></div>
              <div class="text-xs text-gray-500">{{ $a->guide->name ?? '-' }} <span class="text-xs text-gray-400">({{ $a->guide_id ?? '-' }})</span></div>
            </td>
            <td class="px-4 py-3 text-sm">{{ $a->assigned_at ? \Carbon\Carbon::parse($a->assigned_at)->format('d M Y H:i') : '-' }}</td>
            <td class="px-4 py-3 text-sm">
              @php
                $badge = match($a->status) {
                  'pending' => 'bg-yellow-100 text-yellow-800',
                  'accepted' => 'bg-green-100 text-green-800',
                  'declined' => 'bg-red-100 text-red-800',
                  'completed'=> 'bg-blue-100 text-blue-800',
                  default => 'bg-gray-100 text-gray-800'
                };
              @endphp
              <span class="px-2 py-1 rounded text-xs {{ $badge }}">{{ ucfirst($a->status) }}</span>
            </td>
            <td class="px-4 py-3 text-sm text-right">
              {{-- tombol buka modal detail --}}
              <button
                onclick="openAssignmentModal({{ json_encode([
                  'id' => $a->id,
                  'order' => [
                    'customer' => $a->order->customer_name ?? '-',
                    'pickup' => $a->order->pickup_time ? \Carbon\Carbon::parse($a->order->pickup_time)->format('d M Y H:i') : '-',
                    'from' => $a->order->pickup_location ?? '-',
                    'to' => $a->order->destination ?? '-',
                    'product' => $a->order->product?->name ?? '-'
                  ],
                  'driver' => $a->driver?->only(['id','name','phone']),
                  'guide' => $a->guide?->only(['id','name','phone']),
                  'status' => $a->status,
                  'note' => $a->note,
                ]) }})"
                class="px-2 py-1 bg-indigo-600 text-white rounded text-xs">Detail</button>

              <form action="{{ route('assignments.destroy', $a) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus assignment?')">
                @csrf @method('DELETE')
                <button class="px-2 py-1 ml-2 bg-red-600 text-white rounded text-xs">Hapus</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="p-4 text-center text-sm text-gray-500">Belum ada assignment.</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $assignments->links() }}
    </div>
  </div>
</div>

{{-- Modal: assignment detail + action (ACCEPT / DECLINE / COMPLETE jika user adalah driver/guide terkait) --}}
<div x-data="assignmentModal()" x-init="init()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded shadow-lg max-w-2xl w-full p-4 z-50">
    <div class="flex items-start justify-between">
      <h3 class="text-lg font-medium">Detail Assignment #<span x-text="payload.id"></span></h3>
      <button @click="close()" class="text-gray-500">✕</button>
    </div>

    <div class="mt-3 space-y-2 text-sm">
      <div><strong>Customer:</strong> <span x-text="payload.order.customer"></span></div>
      <div><strong>Pickup:</strong> <span x-text="payload.order.pickup"></span></div>
      <div><strong>From / To:</strong> <span x-text="payload.order.from"></span> → <span x-text="payload.order.to"></span></div>
      <div><strong>Product:</strong> <span x-text="payload.order.product"></span></div>
      <div><strong>Driver:</strong> <span x-text="payload.driver?.name ?? '-'"></span> (<span x-text="payload.driver?.id ?? '-'"></span>)</div>
      <div><strong>Guide:</strong> <span x-text="payload.guide?.name ?? '-'"></span> (<span x-text="payload.guide?.id ?? '-'"></span>)</div>
      <div><strong>Note:</strong> <span x-text="payload.note ?? '-'"></span></div>
      <div><strong>Status:</strong> <span x-text="payload.status"></span></div>
    </div>

    <div class="mt-4 flex items-center justify-end space-x-2">
      {{-- jika current user adalah driver/guide terkait, tampilkan action --}}
      @auth
      <template x-if="isCurrentPerformer()">
        <div class="flex items-center space-x-2">
          <form :action="changeStatusUrl('accepted')" method="POST" x-ref="formAccept">
            @csrf
            <input type="hidden" name="status" value="accepted" />
            <button type="button" @click="submitForm($refs.formAccept)" class="px-3 py-2 bg-green-600 text-white rounded">Accept</button>
          </form>

          <form :action="changeStatusUrl('declined')" method="POST" x-ref="formDecline">
            @csrf
            <input type="hidden" name="status" value="declined" />
            <button type="button" @click="submitForm($refs.formDecline)" class="px-3 py-2 bg-red-600 text-white rounded">Decline</button>
          </form>

          <form :action="changeStatusUrl('completed')" method="POST" x-ref="formCompleted">
            @csrf
            <input type="hidden" name="status" value="completed" />
            <button type="button" @click="submitForm($refs.formCompleted)" class="px-3 py-2 bg-blue-600 text-white rounded">Complete</button>
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
  function openAssignmentModal(payload) {
    const event = new CustomEvent('open-assignment-modal', { detail: payload });
    window.dispatchEvent(event);
  }

  function assignmentModal() {
    return {
      open: false,
      payload: {},
      currentUserId: {{ auth()->check() ? auth()->id() : 'null' }},
      currentUserRole: "{{ auth()->check() ? auth()->user()->role : '' }}",
      init() {
        window.addEventListener('open-assignment-modal', (e) => {
          this.payload = e.detail;
          this.open = true;
        });
      },
      close() {
        this.open = false;
        this.payload = {};
      },
      isCurrentPerformer() {
        if (!this.currentUserId) return false;
        // check if current user is driver or guide for this payload
        if (this.currentUserRole === 'driver' && this.payload.driver && this.payload.driver.id == this.currentUserId) return true;
        if (this.currentUserRole === 'guide' && this.payload.guide && this.payload.guide.id == this.currentUserId) return true;
        return false;
      },
      changeStatusUrl(status) {
        // returns url like /assignments/{id}/status
        return `/assignments/${this.payload.id}/status`;
      },
      submitForm(form) {
        // simple confirmation
        if (!confirm('Yakin?')) return;
        form.submit();
      }
    }
  }
</script>
@endpush
@endsection