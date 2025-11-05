@extends('layouts.app')
@section('title','Dashboard - Tugas Saya')

@section('content')
@php
  $ordersThisMonth = $ordersThisMonth ?? 0;
  $assignedThisMonth = $assignedThisMonth ?? 0;
  $completedThisMonth = $completedThisMonth ?? 0;
  $activeDrivers = $activeDrivers ?? 0;
  $personal = $personal ?? [];
  $month = $month ?? \Carbon\Carbon::now()->month;
  $year = $year ?? \Carbon\Carbon::now()->year;
@endphp

<div class="max-w-7xl mx-auto px-4 py-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Tugas Saya</h1>
    <div class="text-sm text-gray-600">Hello, {{ auth()->user()->name }}</div>
  </div>

  {{-- Stats inline --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded shadow p-4">
      <div class="text-sm text-gray-500">Orders ({{ \Carbon\Carbon::create($year, $month, 1)->format('M Y') }})</div>
      <div class="text-2xl font-semibold mt-2">{{ $ordersThisMonth }}</div>
    </div>

    <div class="bg-white rounded shadow p-4">
      <div class="text-sm text-gray-500">Assigned</div>
      <div class="text-2xl font-semibold mt-2">{{ $assignedThisMonth }}</div>
    </div>

    <div class="bg-white rounded shadow p-4">
      <div class="text-sm text-gray-500">Completed</div>
      <div class="text-2xl font-semibold mt-2">{{ $completedThisMonth }}</div>
    </div>

    <div class="bg-white rounded shadow p-4">
      <div class="text-sm text-gray-500">Active Drivers/Guides</div>
      <div class="text-2xl font-semibold mt-2">{{ $activeDrivers }}</div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded shadow p-4">
      <h4 class="font-semibold">My Assignments</h4>
      <p class="text-sm text-gray-600 mt-2">Daftar tugas yang ditugaskan kepada Anda.</p>
      <div class="mt-3"><a href="{{ Route::has('assignments.my') ? route('assignments.my') : '#' }}" class="px-3 py-2 bg-blue-600 text-white rounded">My Tasks</a></div>
    </div>

    <div class="bg-white rounded shadow p-4">
      <h4 class="font-semibold">Work Hours</h4>
      <p class="text-sm text-gray-600 mt-2">Used vs total hours.</p>
      @if(!empty($personal['work_schedule']))
        <div class="mt-3 text-lg font-semibold">{{ $personal['work_schedule']['used_hours'] ?? 0 }}/{{ $personal['work_schedule']['total_hours'] ?? 0 }}</div>
      @else
        <div class="mt-3 text-sm text-gray-500">Work schedule not set.</div>
      @endif
    </div>
  </div>

  {{-- quick link --}}
  <div class="mt-6 bg-white rounded shadow p-4">
    <h4 class="font-semibold">Quick links</h4>
    <div class="mt-3 flex gap-2">
      <a href="{{ Route::has('assignments.my') ? route('assignments.my') : '#' }}" class="px-3 py-2 bg-blue-600 text-white rounded">My Assignments</a>
      <a href="{{ Route::has('work-schedules.index') ? route('work-schedules.index') : '#' }}" class="px-3 py-2 bg-gray-800 text-white rounded">Work Schedules</a>
    </div>
  </div>
</div>
@endsection
