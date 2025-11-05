@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-6xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Users</h1>

    <div class="flex items-center space-x-2">
      <a href="{{ route('users.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Tambah User</a>
    </div>
  </div>

  {{-- filters --}}
  <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2">
    <div>
      <label class="block text-xs text-gray-600">Role</label>
      <select name="role" class="mt-1 block w-full rounded border-gray-200">
        <option value="">Semua</option>
        @foreach($roles as $r)
          <option value="{{ $r }}" @if(request('role') == $r) selected @endif>{{ ucfirst(str_replace('_',' ', $r)) }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-xs text-gray-600">Cari</label>
      <input name="search" value="{{ request('search') }}" placeholder="nama / email / telepon" class="mt-1 block w-full rounded border-gray-200" />
    </div>

    <div class="col-span-2 flex items-end space-x-2">
      <button class="px-3 py-2 bg-gray-800 text-white rounded">Filter</button>
      <a href="{{ route('users.index') }}" class="px-3 py-2 bg-gray-200 rounded">Reset</a>
    </div>
  </form>

  <div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium">ID</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Nama</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Email / Phone</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Role</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Jam Bulanan</th>
          <th class="px-4 py-2 text-right text-sm font-medium">Aksi</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100">
        @forelse($users as $u)
          @php
            // Siapkan payload aman untuk modal
            $payload = [
              'id' => $u->id,
              'name' => $u->name,
              'email' => $u->email,
              'phone' => $u->phone,
              'role' => $u->role,
              'join_date' => $u->join_date ? \Carbon\Carbon::parse($u->join_date)->format('d M Y') : '-',
              'monthly_work_limit' => $u->monthly_work_limit,
              'used_hours' => $u->used_hours,
            ];
            $payload_b64 = base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
          @endphp

          <tr>
            <td class="px-4 py-3 text-sm">{{ $u->id }}</td>
            <td class="px-4 py-3 text-sm">
              <div class="font-medium">{{ $u->name }}</div>
              <div class="text-xs text-gray-500">Join: {{ $u->join_date ? \Carbon\Carbon::parse($u->join_date)->format('d M Y') : '-' }}</div>
            </td>
            <td class="px-4 py-3 text-sm">
              <div>{{ $u->email ?? '-' }}</div>
              <div class="text-xs text-gray-500">{{ $u->phone ?? '-' }}</div>
            </td>
            <td class="px-4 py-3 text-sm">{{ ucfirst(str_replace('_',' ', $u->role)) }}</td>
            <td class="px-4 py-3 text-sm">
              @if(in_array($u->role, ['driver','guide']))
                {{ $u->used_hours ?? 0 }} / {{ $u->monthly_work_limit ?? '—' }}
              @else
                -
              @endif
            </td>
            <td class="px-4 py-3 text-sm text-right">
              {{-- tombol detail: gunakan data-payload-b64 agar aman --}}
              <button
                type="button"
                class="px-2 py-1 bg-indigo-600 text-white rounded text-xs"
                data-payload-b64="{{ $payload_b64 }}"
                onclick="openUserModal(this)"
              >Detail</button>

              <a href="{{ route('users.edit', $u) }}" class="px-2 py-1 ml-1 bg-yellow-400 text-white rounded text-xs">Edit</a>

              <form action="{{ route('users.destroy', $u) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus user ini?')">
                @csrf @method('DELETE')
                <button class="px-2 py-1 ml-1 bg-red-600 text-white rounded text-xs">Hapus</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="p-4 text-center text-gray-500">Belum ada user.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $users->links() }}
    </div>
  </div>
</div>

{{-- Modal user detail --}}
<div x-data="userModal()" x-init="init()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded shadow-lg max-w-lg w-full p-4 z-50">
    <div class="flex items-start justify-between">
      <h3 class="text-lg font-medium">User #<span x-text="payload.id"></span> — <span x-text="payload.name"></span></h3>
      <button @click="close()" class="text-gray-500">✕</button>
    </div>

    <div class="mt-3 text-sm space-y-2">
      <div><strong>Email:</strong> <span x-text="payload.email || '-'"></span></div>
      <div><strong>Phone:</strong> <span x-text="payload.phone || '-'"></span></div>
      <div><strong>Role:</strong> <span x-text="payload.role"></span></div>
      <div><strong>Join:</strong> <span x-text="payload.join_date"></span></div>
      <div x-show="payload.role == 'driver' || payload.role == 'guide'">
        <strong>Jam:</strong>
        <span x-text="(payload.used_hours ?? 0) + ' / ' + (payload.monthly_work_limit ?? '-')"></span>
      </div>
    </div>

    <div class="mt-4 flex items-center justify-end">
      <a :href="'/users/' + payload.id + '/edit'" class="px-3 py-2 bg-yellow-400 text-white rounded">Edit</a>
      <button @click="close()" class="ml-2 px-3 py-2 bg-gray-200 rounded">Close</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function userModal() {
    return {
      open: false,
      payload: {},
      init() {
        window.addEventListener('open-user-modal', (e) => {
          this.payload = e.detail || {};
          this.open = true;
        });
      },
      close() { this.open = false; this.payload = {}; }
    }
  }

  // Terima element tombol, baca data-payload-b64, decode lalu dispatch event
  function openUserModal(el) {
    try {
      const raw = el.getAttribute('data-payload-b64');
      if (!raw) return;
      const json = atob(raw);
      const payload = JSON.parse(json);
      window.dispatchEvent(new CustomEvent('open-user-modal', { detail: payload }));
    } catch (err) {
      console.error('openUserModal error', err);
    }
  }
</script>
@endpush

@endsection
