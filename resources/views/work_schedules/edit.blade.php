@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-2xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Edit Work Schedule</h1>
    <a href="{{ route('work-schedules.index', ['year'=>$workSchedule->year,'month'=>$workSchedule->month]) }}" class="px-3 py-2 bg-gray-200 rounded">Kembali</a>
  </div>

  <form action="{{ route('work-schedules.update', $workSchedule) }}" method="POST" class="bg-white p-4 rounded shadow">
    @csrf @method('PUT')

    <div class="mb-3 text-sm text-gray-600">
      <div><strong>User:</strong> {{ $workSchedule->user->name }} ({{ ucfirst($workSchedule->user->role) }})</div>
      <div><strong>Period:</strong> {{ \Carbon\Carbon::create()->month($workSchedule->month)->format('F') }} {{ $workSchedule->year }}</div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm">Total Hours</label>
        <input name="total_hours" type="number" min="0" value="{{ old('total_hours', $workSchedule->total_hours) }}" required class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Used Hours</label>
        <input name="used_hours" type="number" min="0" value="{{ old('used_hours', $workSchedule->used_hours) }}" class="mt-1 block w-full rounded border-gray-200" />
        <div class="text-xs text-gray-500 mt-1">Jika kosong, sistem akan menyesuaikan used_hours agar tidak melebihi total_hours.</div>
      </div>
    </div>

    <div class="mt-4 flex items-center space-x-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
      <a href="{{ route('work-schedules.index', ['year'=>$workSchedule->year,'month'=>$workSchedule->month]) }}" class="px-4 py-2 bg-gray-200 rounded">Batal</a>
    </div>
  </form>
</div>
@endsection
