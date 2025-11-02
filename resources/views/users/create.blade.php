@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-2xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Tambah User</h1>
    <a href="{{ route('users.index') }}" class="px-3 py-2 bg-gray-200 rounded">Kembali</a>
  </div>

  <form action="{{ route('users.store') }}" method="POST" class="bg-white p-4 rounded shadow">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm">Nama</label>
        <input name="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Role</label>
        <select name="role" required class="mt-1 block w-full rounded border-gray-200">
          @foreach($roles as $r)
            <option value="{{ $r }}" @if(old('role')==$r) selected @endif>{{ ucfirst(str_replace('_',' ',$r)) }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm">Email</label>
        <input name="email" value="{{ old('email') }}" required type="email" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Phone</label>
        <input name="phone" value="{{ old('phone') }}" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Join Date</label>
        <input name="join_date" type="date" value="{{ old('join_date') }}" class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Password</label>
        <input name="password" type="password" required class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Confirm Password</label>
        <input name="password_confirmation" type="password" required class="mt-1 block w-full rounded border-gray-200" />
      </div>

      <div>
        <label class="block text-sm">Monthly Work Limit (jam, untuk driver/guide)</label>
        <input name="monthly_work_limit" type="number" min="0" value="{{ old('monthly_work_limit') }}" class="mt-1 block w-full rounded border-gray-200" />
      </div>
    </div>

    <div class="mt-4 flex items-center space-x-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
      <a href="{{ route('users.index') }}" class="px-4 py-2 bg-gray-200 rounded">Batal</a>
    </div>
  </form>
</div>
@endsection
