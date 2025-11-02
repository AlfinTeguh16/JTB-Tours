@extends('layouts.app')

@section('content')
<div class="container mx-auto">
  <h2 class="text-2xl mb-4">Driver</h2>
  <div class="grid grid-cols-3 gap-4">
    @foreach($drivers as $d)
      <div class="bg-white p-4 rounded shadow">
        <h3 class="font-semibold">{{ $d->name }}</h3>
        <p class="text-sm">{{ $d->phone }}</p>
        <p class="text-xs mt-2">Jam kerja: {{ $d->used_hours }} / {{ $d->monthly_work_limit }}</p>
        <p class="text-xs">Status: <span class="font-medium">{{ $d->status }}</span></p>
        <div class="mt-3 flex space-x-2">
          <a href="{{ route('assignments.create', ['order' => request('order')]) }}" class="text-sm px-3 py-1 bg-blue-500 text-white rounded">Beri Tugas</a>
          <a href="{{ route('users.show', $d->id) }}" class="text-sm px-3 py-1 border rounded">Detail</a>
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection
