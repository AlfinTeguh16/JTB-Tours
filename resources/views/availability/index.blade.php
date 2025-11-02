@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Availability — Driver & Guide</h1>
    <div class="flex items-center space-x-2">
      <form method="GET" class="flex items-center space-x-2">
        <input name="search" value="{{ request('search') }}" placeholder="cari nama / telepon" class="px-2 py-1 rounded border-gray-200" />
        <select name="status" class="px-2 py-1 rounded border-gray-200">
          <option value="">Semua</option>
          <option value="online" @if(request('status')=='online') selected @endif>Online</option>
          <option value="offline" @if(request('status')=='offline') selected @endif>Offline</option>
        </select>
        <button class="px-3 py-1 bg-gray-800 text-white rounded">Filter</button>
      </form>
    </div>
  </div>

  <div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium">ID</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Nama</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Role</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Status</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Jam Bulan Ini</th>
          <th class="px-4 py-2 text-right text-sm font-medium">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($users as $u)
          <tr>
            <td class="px-4 py-3 text-sm">{{ $u->id }}</td>
            <td class="px-4 py-3 text-sm">
              <div class="font-medium">{{ $u->name }}</div>
              <div class="text-xs text-gray-500">{{ $u->phone }}</div>
            </td>
            <td class="px-4 py-3 text-sm">{{ ucfirst($u->role) }}</td>
            <td class="px-4 py-3 text-sm">
              <span class="px-2 py-1 rounded text-xs {{ $u->status === 'online' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">{{ ucfirst($u->status ?? 'offline') }}</span>
            </td>
            <td class="px-4 py-3 text-sm">
              @php
                $ws = $schedules[$u->id] ?? null;
              @endphp
              @if($ws)
                {{ $ws->used_hours ?? 0 }} / {{ $ws->total_hours ?? '—' }} jam
              @else
                -
              @endif
            </td>
            <td class="px-4 py-3 text-right text-sm">
              @if(in_array(auth()->user()->role, ['super_admin','admin']))
                <form action="{{ route('availability.force', $u) }}" method="POST" class="inline-block" onsubmit="return confirm('Ubah status user?')">
                  @csrf
                  <input type="hidden" name="status" value="{{ $u->status === 'online' ? 'offline' : 'online' }}">
                  <button class="px-3 py-1 rounded bg-indigo-600 text-white text-sm">@if($u->status === 'online') Set Offline @else Set Online @endif</button>
                </form>
              @else
                <span class="text-xs text-gray-500">—</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="p-4 text-center text-gray-500">Tidak ada driver/guide.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $users->links() }}
    </div>
  </div>
</div>
@endsection
