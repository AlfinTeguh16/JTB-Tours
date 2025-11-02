@extends('layouts.app')

@section('title','Detail User - JTB Tours')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
  <h2 class="text-xl font-semibold mb-4">Detail User</h2>

  <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
    <dt class="text-sm text-gray-500">Nama</dt>
    <dd class="font-medium">{{ $user->name }}</dd>

    <dt class="text-sm text-gray-500">Role</dt>
    <dd class="font-medium">{{ $user->role }}</dd>

    <dt class="text-sm text-gray-500">Email</dt>
    <dd class="font-medium">{{ $user->email }}</dd>

    <dt class="text-sm text-gray-500">Telepon</dt>
    <dd class="font-medium">{{ $user->phone }}</dd>

    <dt class="text-sm text-gray-500">Waktu Masuk</dt>
    <dd class="font-medium">{{ $user->join_date?->format('Y-m-d') }}</dd>

    <dt class="text-sm text-gray-500">Jam kerja Bulanan</dt>
    <dd class="font-medium">{{ $user->used_hours }} / {{ $user->monthly_work_limit }}</dd>

    <dt class="text-sm text-gray-500">Status</dt>
    <dd class="font-medium capitalize">{{ $user->status }}</dd>
  </dl>

  <div class="mt-6">
    <a href="{{ route('users.edit',$user->id) }}" class="bg-indigo-600 text-white px-3 py-2 rounded">Edit</a>
    <a href="{{ route('users.index') }}" class="ml-2 px-3 py-2 border rounded">Kembali</a>
  </div>
</div>
@endsection
