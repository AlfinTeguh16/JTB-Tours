{{-- resources/views/assignments/my.blade.php --}}
@extends('layouts.app')

@section('title', 'Tugas Saya')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-4xl mx-auto p-4">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold">Tugas Saya</h1>
  </div>

  @php
    // Kelompokkan assignment berdasarkan status
    $pending = $assignments->where('status', 'pending');
    $accepted = $assignments->where('status', 'accepted');
    $running  = $assignments->where('status', 'in_progress');
    $history = $assignments->whereIn('status', ['completed', 'declined']);
  @endphp

  {{-- === Bagian 1: Tugas Pending === --}}
  <div class="mb-8">
    <div class="flex items-center gap-2 mb-3">
      <h2 class="text-lg font-medium text-gray-800">Tugas Menunggu</h2>
      <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full">
        {{ $pending->count() }}
      </span>
    </div>

    @if($pending->isNotEmpty())
      <div class="space-y-3">
        @foreach($pending as $a)
          @include('assignments._assignment-card', ['a' => $a, 'showActions' => true])
        @endforeach
      </div>
    @else
      <div class="text-sm text-gray-500 italic">Tidak ada tugas menunggu.</div>
    @endif
  </div>

  {{-- === Bagian 2: Tugas Saya Jalankan (Running) === --}}
  <div class="mb-8">
    <div class="flex items-center gap-2 mb-3">
      <h2 class="text-lg font-medium text-gray-800">Tugas Berjalan</h2>
      <span class="bg-indigo-100 text-indigo-800 text-xs px-2 py-0.5 rounded-full">
        {{ $running->count() }}
      </span>
    </div>

    @if($running->isNotEmpty())
      <div class="space-y-3">
        @foreach($running as $a)
          @include('assignments._assignment-card', ['a' => $a, 'showActions' => true])
        @endforeach
      </div>
    @else
      <div class="text-sm text-gray-500 italic">Tidak ada tugas yang sedang berjalan.</div>
    @endif
  </div>

  {{-- === Bagian 3: Tugas Diterima (Siap Dimulai) === --}}
  <div class="mb-8">
    <div class="flex items-center gap-2 mb-3">
      <h2 class="text-lg font-medium text-gray-800">Tugas Diterima (Accepted)</h2>
      <span class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full">
        {{ $accepted->count() }}
      </span>
    </div>

    @if($accepted->isNotEmpty())
      <div class="space-y-3">
        @foreach($accepted as $a)
          @include('assignments._assignment-card', ['a' => $a, 'showActions' => true])
        @endforeach
      </div>
    @else
      <div class="text-sm text-gray-500 italic">Tidak ada tugas yang diterima (menunggu dimulai).</div>
    @endif
  </div>

  {{-- === Bagian 3: Riwayat (Completed & Declined) === --}}
  <div>
    <div class="flex items-center gap-2 mb-3">
      <h2 class="text-lg font-medium text-gray-800">Riwayat Tugas</h2>
      <span class="bg-gray-100 text-gray-800 text-xs px-2 py-0.5 rounded-full">
        {{ $history->count() }}
      </span>
    </div>

    @if($history->isNotEmpty())
      <div class="space-y-3">
        @foreach($history as $a)
          @include('assignments._assignment-card', ['a' => $a, 'showActions' => false])
        @endforeach
      </div>

      {{-- Pagination hanya untuk riwayat jika > 10 --}}
      @if($history->count() > 10)
        <div class="mt-4 text-center">
          <button type="button"
            class="text-sm text-blue-600 hover:underline"
            onclick="alert('Fitur riwayat lengkap akan dikembangkan di versi berikutnya.')">
            Lihat lebih banyak riwayat →
          </button>
        </div>
      @endif
    @else
      <div class="text-sm text-gray-500 italic">Belum ada riwayat tugas.</div>
    @endif
  </div>
</div>

{{-- Modal tetap sama — tidak diubah --}}
<div
  x-data="assignmentModal()"
  x-init="init()"
  x-show="open"
  x-cloak
  class="fixed inset-0 z-50 flex items-center justify-center p-4"
  style="display: none;"
>
  <div class="absolute inset-0 bg-black/40" @click="close()" aria-hidden="true"></div>
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
      <div><strong>Driver:</strong> <span x-text="payload.driver && payload.driver.name ? payload.driver.name : '-'"></span></div>
      <div><strong>Guide:</strong> <span x-text="payload.guide && payload.guide.name ? payload.guide.name : '-'"></span></div>
      <div><strong>Note:</strong> <span x-text="payload.note ? payload.note : '-'"></span></div>
      <div><strong>Status:</strong> <span x-text="payload.status ? payload.status : '-'"></span></div>
    </div>

    <div class="mt-4 flex items-center justify-end space-x-2">
      @auth
      <template x-if="isCurrentPerformer()">
        <div class="flex items-center space-x-2">
            
          {{-- PENDING: Terima / Tolak --}}
          <template x-if="payload.status === 'pending'">
             <div class="flex space-x-2">
                <form x-bind:action="changeStatusUrl('accepted')" method="POST" x-ref="formAccept">
                  <input type="hidden" name="_token" value="{{ csrf_token() }}">
                  <input type="hidden" name="status" value="accepted">
                  <button type="button" @click="confirmAndSubmit($refs.formAccept, 'Terima tugas ini?')" class="px-3 py-2 bg-green-600 text-white rounded text-sm">Terima Tugas</button>
                </form>
                
                <button type="button" @click="showRejectReason = true" class="px-3 py-2 bg-red-600 text-white rounded text-sm" x-show="!showRejectReason">Tolak</button>
             </div>
          </template>

          {{-- ACCEPTED: Mulai Jalan (In Progress) --}}
          <template x-if="payload.status === 'accepted'">
             <form x-bind:action="changeStatusUrl('in_progress')" method="POST" x-ref="formStart">
               <input type="hidden" name="_token" value="{{ csrf_token() }}">
               <input type="hidden" name="status" value="in_progress">
               <button type="button" @click="confirmAndSubmit($refs.formStart, 'Mulai kerjakan tugas (start job)?')" class="px-3 py-2 bg-blue-600 text-white rounded text-sm">Mulai Jalan / Kerjakan</button>
             </form>
          </template>

          {{-- IN PROGRESS: Selesai --}}
          <template x-if="payload.status === 'in_progress'">
            <form x-bind:action="changeStatusUrl('completed')" method="POST" x-ref="formCompleted">
              <input type="hidden" name="_token" value="{{ csrf_token() }}">
              <input type="hidden" name="status" value="completed">
              <button type="button" @click="confirmAndSubmit($refs.formCompleted, 'Tugas sudah selesai?')" class="px-3 py-2 bg-indigo-600 text-white rounded text-sm">Tugas Selesai</button>
            </form>
          </template>

           {{-- Form Tolak (Hidden by default) --}}
           <div x-show="showRejectReason" class="w-full mt-2" style="display:none;">
             <div class="flex flex-col space-y-2 p-3 border border-red-200 rounded bg-red-50">
                <p class="text-xs font-bold text-red-800">Alasan Penolakan:</p>
                <form x-bind:action="changeStatusUrl('declined')" method="POST" x-ref="formDecline">
                  <input type="hidden" name="_token" value="{{ csrf_token() }}">
                  <input type="hidden" name="status" value="declined">
                  <textarea name="rejection_reason" class="w-full text-sm border-gray-300 rounded mb-2" placeholder="Tulis alasan..." required rows="2"></textarea>
                  <div class="flex space-x-2 justify-end">
                      <button type="button" @click="showRejectReason = false" class="px-3 py-1 bg-gray-300 text-gray-700 rounded text-xs hover:bg-gray-400">Batal</button>
                      <button type="button" @click="confirmAndSubmit($refs.formDecline, 'Yakin menolak tugas ini?')" class="px-3 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">Kirim Penolakan</button>
                  </div>
                </form>
             </div>
           </div>

        </div>
      </template>
      @endauth

      <button @click="close()" class="px-3 py-2 bg-gray-200 rounded text-sm">Close</button>
    </div>
  </div>
</div>

{{-- === Extract Card ke Partial (opsional tapi direkomendasikan) === --}}
@push('inline-styles')
<style>
  .status-badge {
    @apply px-2 py-0.5 text-xs font-medium rounded-full;
  }
  .status-pending { @apply bg-yellow-100 text-yellow-800; }
  .status-accepted { @apply bg-green-100 text-green-800; }
  .status-in_progress { @apply bg-indigo-100 text-indigo-800; }
  .status-completed { @apply bg-blue-100 text-blue-800; }
  .status-declined { @apply bg-red-100 text-red-800; }
</style>
@endpush

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
      showRejectReason: false,
      currentUserId: {!! json_encode(auth()->check() ? auth()->id() : null) !!},
      currentUserRole: {!! json_encode(auth()->check() ? auth()->user()->role : null) !!},

      init() {
        window.addEventListener('open-assignment-modal', (e) => {
          this.payload = e.detail || {};
          this.open = true;
          this.showRejectReason = false;
        });
      },
      close() {
        this.open = false;
        this.payload = {};
      },
      isCurrentPerformer() {
        if (!this.currentUserId) return false;
        if (this.currentUserRole === 'driver' && this.payload.driver && this.payload.driver.id == this.currentUserId) return true;
        if (this.currentUserRole === 'guide' && this.payload.guide && this.payload.guide.id == this.currentUserId) return true;
        return false;
      },
      changeStatusUrl(status) {
        return `/assignments/${this.payload.id}/status`;
      },
      confirmAndSubmit(formRef, msg = 'Yakin ingin melakukan aksi ini?') {
        if (!confirm(msg)) return;
        formRef.submit();
      }
    }
  }

  // Timer: Update live duration every second
  function updateTimers() {
      const timers = document.querySelectorAll('.live-timer');
      const now = Math.floor(Date.now() / 1000);
      
      timers.forEach(el => {
          const start = parseInt(el.getAttribute('data-start'));
          if (!start) return;
          
          let diff = now - start;
          if (diff < 0) diff = 0;
          
          const hours = Math.floor(diff / 3600);
          const minutes = Math.floor((diff % 3600) / 60);
          const seconds = diff % 60;
          
          el.innerText = 
             String(hours).padStart(2, '0') + ':' +
             String(minutes).padStart(2, '0') + ':' +
             String(seconds).padStart(2, '0');
      });
  }
  setInterval(updateTimers, 1000);
  document.addEventListener('DOMContentLoaded', updateTimers);
</script>
@endpush

@endsection