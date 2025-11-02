{{-- Partial: flash messages dan modal dasar (pakai Alpine.js) --}}
<div class="fixed inset-0 z-40 pointer-events-none">
  {{-- Flash messages --}}
  @if(session('success') || session('error'))
    <div class="fixed top-4 right-4 z-50 pointer-events-auto">
      @if(session('success'))
        <div class="mb-2 px-4 py-3 bg-green-600 text-white rounded shadow">
          {{ session('success') }}
        </div>
      @endif
      @if(session('error'))
        <div class="px-4 py-3 bg-red-600 text-white rounded shadow">
          {{ session('error') }}
        </div>
      @endif
    </div>
  @endif
</div>

{{-- Generic modal component (Tailwind + Alpine)
  Usage:
    <div x-data="{ open:false, payload: {} }" x-show="open"> ... </div>
  We provide a small helper modal layout below.
--}}
@push('scripts')
<script>
  // helper to open modal with data
  function openAssignmentModal(data) {
    const evt = new CustomEvent('open-assignment-modal', { detail: data });
    window.dispatchEvent(evt);
  }
</script>
@endpush
