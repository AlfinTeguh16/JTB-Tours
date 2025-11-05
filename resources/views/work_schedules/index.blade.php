@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-6xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold">Work Schedules</h1>
      <div class="text-sm text-gray-500">Month: {{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}</div>
    </div>

    <div class="flex items-center gap-2">
      {{-- Generate for all --}}
      <form action="{{ route('work-schedules.generate') }}" method="POST" class="flex items-center gap-2">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded text-sm">Generate for All</button>
      </form>

      {{-- Reset used hours modal --}}
      <button @click="openResetModal()" class="px-3 py-2 bg-yellow-500 text-white rounded text-sm">Reset Used Hours</button>
    </div>
  </div>

  {{-- Filters: month / year --}}
  <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
    <div>
      <label class="block text-xs text-gray-600">Year</label>
      <input name="year" type="number" min="2000" max="2100" value="{{ $year }}" class="mt-1 block w-full rounded border-gray-200 px-3 py-2" />
    </div>

    <div>
      <label class="block text-xs text-gray-600">Month</label>
      <select name="month" class="mt-1 block w-full rounded border-gray-200 px-3 py-2">
        @for($m=1;$m<=12;$m++)
          <option value="{{ $m }}" @if($m == $month) selected @endif>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
        @endfor
      </select>
    </div>

    <div>
      <label class="block text-xs text-gray-600">Cari (nama)</label>
      <input name="q" value="{{ request('q') }}" placeholder="cari nama driver/guide" class="mt-1 block w-full rounded border-gray-200 px-3 py-2" />
    </div>

    <div class="flex items-center">
      <button class="px-3 py-2 bg-gray-800 text-white rounded">Terapkan</button>
      <a href="{{ route('work-schedules.index') }}" class="ml-2 px-3 py-2 bg-gray-200 rounded">Reset</a>
    </div>
  </form>

  {{-- Bulk update form --}}
  <form action="{{ route('work-schedules.bulkUpdate') }}" method="POST">
    @csrf
    <input type="hidden" name="year" value="{{ $year }}">
    <input type="hidden" name="month" value="{{ $month }}">

    <div class="bg-white rounded shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-medium"><input type="checkbox" id="select_all" /></th>
            <th class="px-4 py-2 text-left text-sm font-medium">ID</th>
            <th class="px-4 py-2 text-left text-sm font-medium">Nama</th>
            <th class="px-4 py-2 text-left text-sm font-medium">Role</th>
            <th class="px-4 py-2 text-left text-sm font-medium">Total Hours</th>
            <th class="px-4 py-2 text-left text-sm font-medium">Used Hours</th>
            <th class="px-4 py-2 text-right text-sm font-medium">Aksi</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
          @forelse($users as $u)
            @php
              $ws = $schedules[$u->id] ?? null;
              $total = $ws ? $ws->total_hours : ($u->monthly_work_limit ?? 200);
              $used = $ws ? ($ws->used_hours ?? 0) : 0;
            @endphp
            <tr>
              <td class="px-4 py-3 text-sm">
                <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="row_checkbox" />
              </td>
              <td class="px-4 py-3 text-sm">{{ $u->id }}</td>
              <td class="px-4 py-3 text-sm">
                <div class="font-medium">{{ $u->name }}</div>
                <div class="text-xs text-gray-500">{{ $u->email ?? '-' }} · {{ $u->phone ?? '-' }}</div>
              </td>
              <td class="px-4 py-3 text-sm">{{ ucfirst($u->role) }}</td>

              <td class="px-4 py-3 text-sm">
                {{-- input total_hours per user --}}
                <input name="schedules[{{ $u->id }}]" type="number" min="0" value="{{ old('schedules.'.$u->id, $total) }}" class="w-28 px-2 py-1 rounded border-gray-200" />
              </td>

              <td class="px-4 py-3 text-sm">
                <div>{{ $used }} jam</div>
              </td>

              <td class="px-4 py-3 text-sm text-right">
                @if($ws)
                  <a href="{{ route('work-schedules.edit', $ws) }}" class="px-2 py-1 bg-yellow-400 text-white rounded text-xs">Edit</a>
                @else
                  <span class="text-xs text-gray-500">—</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="p-4 text-center text-gray-500">Tidak ada driver/guide.</td></tr>
          @endforelse
        </tbody>
      </table>

      <div class="p-4 flex items-center justify-between">
        <div class="space-x-2">
          <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded">Simpan Bulk</button>
          <button type="button" onclick="document.querySelector('form[action=\'{{ route('work-schedules.bulkUpdate') }}\']').reset()" class="px-3 py-2 bg-gray-200 rounded">Reset Form</button>
        </div>

        <div>
          {{-- {{ $users->links() }} --}}
        </div>
      </div>
    </div>
  </form>
</div>

{{-- Reset modal (Alpine) --}}
<div x-data="{ open: false, all: true }" x-init="window.openResetModal = ()=>{ open=true }" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="fixed inset-0 bg-black/40" @click="open=false"></div>
  <div class="bg-white rounded shadow-lg max-w-lg w-full p-4 z-50">
    <h3 class="text-lg font-medium">Reset Used Hours</h3>
    <p class="mt-2 text-sm text-gray-600">Reset used hours menjadi 0 untuk bulan <strong>{{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}</strong>.</p>

    <form action="{{ route('work-schedules.reset') }}" method="POST" class="mt-4">
      @csrf
      <input type="hidden" name="year" value="{{ $year }}">
      <input type="hidden" name="month" value="{{ $month }}">

      <div class="flex items-center gap-3">
        <label class="inline-flex items-center">
          <input type="radio" name="mode" value="all" x-model="all" checked class="mr-2" />
          Semua user pada bulan ini
        </label>
        <label class="inline-flex items-center">
          <input type="radio" name="mode" value="selected" x-model="all" class="mr-2" />
          Pilih beberapa (gunakan checkbox di tabel)
        </label>
      </div>

      <div class="mt-3 text-sm text-gray-600">Catatan: jika memilih "Pilihan", centang user pada tabel lalu submit.</div>

      <div class="mt-4 flex items-center justify-end gap-2">
        <button type="button" @click="open=false" class="px-3 py-2 bg-gray-200 rounded">Batal</button>
        <button type="submit" class="px-3 py-2 bg-yellow-500 text-white rounded">Reset</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  // select all checkbox
  document.addEventListener('DOMContentLoaded', function(){
    const selectAll = document.getElementById('select_all');
    if (selectAll) {
      selectAll.addEventListener('change', function(){
        document.querySelectorAll('.row_checkbox').forEach(cb => cb.checked = selectAll.checked);
      });
    }
  });

  // helper to open reset modal from outside
  function openResetModal(){
    if (window.openResetModal) window.openResetModal();
  }
</script>
@endpush

@endsection
