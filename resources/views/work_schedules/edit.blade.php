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

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-3 text-sm text-gray-600">
      <div><strong>User:</strong> {{ $workSchedule->user->name }} ({{ ucfirst($workSchedule->user->role) }})</div>
      <div><strong>Period:</strong> {{ \Carbon\Carbon::create()->month($workSchedule->month)->format('F') }} {{ $workSchedule->year }}</div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <x-text-input type="number" name="total_hours" label="Total Hours" :value="old('total_hours', $workSchedule->total_hours)" min="0" required />
      </div>

      <div>
        <x-text-input type="number" name="used_hours" label="Used Hours" :value="old('used_hours', $workSchedule->used_hours)" min="0" />
        <div class="text-xs text-gray-500 mt-1">Jika kosong, sistem akan menyesuaikan used_hours agar tidak melebihi total_hours.</div>
      </div>
    </div>

    <div class="mt-4 flex space-x-2">
      <x-primary-button>Simpan</x-primary-button>
      <x-secondary-button :href="route('work-schedules.index', ['year'=>$workSchedule->year,'month'=>$workSchedule->month])">Batal</x-secondary-button>
    </div>
  </form>
</div>
@endsection
