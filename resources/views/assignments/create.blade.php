@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

@php
    use Carbon\Carbon;

    $month = $month ?? now()->month;
    $year  = $year ?? now()->year;
    $periodLabel = Carbon::create($year, $month, 1)->format('F Y');
@endphp

<div class="max-w-3xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Buat Assignment</h1>
    <a href="{{ route('assignments.index') }}" class="px-3 py-2 bg-gray-200 rounded text-sm">
      Kembali
    </a>
  </div>

  <form action="{{ route('assignments.store') }}" method="POST" class="bg-white p-4 rounded shadow space-y-4">
    @csrf

    {{-- Error message --}}
    @if($errors->any())
      <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
        <div class="font-semibold mb-1">Terjadi kesalahan:</div>
        <ul class="list-disc list-inside space-y-0.5">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      {{-- Pilih Order --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">Pilih Order</label>
        <select
          name="order_id"
          required
          class="mt-1 block w-full rounded border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
          <option value="">-- pilih order --</option>
          @foreach($orders as $o)
            @php
              $pickup = $o->pickup_time
                ? Carbon::parse($o->pickup_time)->format('d M Y H:i')
                : '-';
            @endphp
            <option
              value="{{ $o->id }}"
              @selected(old('order_id', $order->id ?? null) == $o->id)
            >
              #{{ $o->id }} — {{ $o->customer_name }} / {{ $pickup }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Pilih Driver + Jam Kerja Bulan Ini --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">
          Pilih Driver
          <span class="text-xs text-gray-400">
            (jam kerja bulan {{ $periodLabel }})
          </span>
        </label>
        <select
          name="driver_id"
          required
          class="mt-1 block w-full rounded border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
          <option value="">-- pilih driver --</option>
          @foreach($drivers as $d)
            @php
              $schedule = $driverSchedules[$d->id] ?? null;
              $totalHours = (int) ($schedule->total_hours ?? $d->monthly_work_limit ?? 200);
              $usedHours  = (float) ($schedule->used_hours ?? 0);
              $remaining  = max(0, $totalHours - $usedHours);

              // warna ringan di text kalau sudah penuh
              $optionClass = $remaining <= 0 ? 'text-gray-400' : '';
            @endphp
            <option
              value="{{ $d->id }}"
              @selected(old('driver_id') == $d->id)
              @if($remaining <= 0) disabled @endif
              class="{{ $optionClass }}"
            >
              {{ $d->name }}
              @if($d->phone)
                ({{ $d->phone }})
              @endif
              — {{ $usedHours }} / {{ $totalHours }} jam
              (sisa {{ $remaining }}h)
              @if($remaining <= 0)
                — Penuh
              @endif
            </option>
          @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">
          Angka menunjukkan jam kerja driver bulan ini: terpakai / total / sisa.
          Driver yang jam kerjanya penuh akan dinonaktifkan dari pilihan.
        </p>
      </div>

      {{-- Pilih Guide + Jam Kerja Bulan Ini (opsional) --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">
          Pilih Guide (opsional)
          <span class="text-xs text-gray-400">
            (jam kerja bulan {{ $periodLabel }})
          </span>
        </label>
        <select
          name="guide_id"
          class="mt-1 block w-full rounded border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
          <option value="">-- tidak ada --</option>
          @foreach($guides as $g)
            @php
              $schedule = $guideSchedules[$g->id] ?? null;
              $totalHours = (int) ($schedule->total_hours ?? $g->monthly_work_limit ?? 200);
              $usedHours  = (float) ($schedule->used_hours ?? 0);
              $remaining  = max(0, $totalHours - $usedHours);
              $optionClass = $remaining <= 0 ? 'text-gray-400' : '';
            @endphp
            <option
              value="{{ $g->id }}"
              @selected(old('guide_id') == $g->id)
              @if($remaining <= 0) disabled @endif
              class="{{ $optionClass }}"
            >
              {{ $g->name }}
              @if($g->phone)
                ({{ $g->phone }})
              @endif
              — {{ $usedHours }} / {{ $totalHours }} jam
              (sisa {{ $remaining }}h)
              @if($remaining <= 0)
                — Penuh
              @endif
            </option>
          @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">
          Sisa jam kerja guide membantu memastikan tidak melampaui kuota bulan ini.
        </p>
      </div>

      {{-- Catatan --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">Catatan</label>
        <input
          name="note"
          value="{{ old('note') }}"
          class="mt-1 block w-full rounded border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
          placeholder="Opsional"
        />
      </div>
    </div>

    <div class="pt-2">
      <button
        class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700"
      >
        Buat Assignment
      </button>
      <a
        href="{{ route('assignments.index') }}"
        class="ml-2 px-4 py-2 bg-gray-200 rounded text-sm hover:bg-gray-300"
      >
        Batal
      </a>
    </div>
  </form>
</div>
@endsection
