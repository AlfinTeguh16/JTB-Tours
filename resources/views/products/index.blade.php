@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-5xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Products</h1>

    <div class="flex items-center space-x-2">
      <a href="{{ route('products.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Tambah Product</a>
    </div>
  </div>

  <form method="GET" class="mb-4 flex gap-2">
    <input name="search" value="{{ request('search') }}" placeholder="cari nama atau deskripsi" class="px-3 py-2 rounded border-gray-200 w-full" />
    <button class="px-3 py-2 bg-gray-800 text-white rounded">Cari</button>
    <a href="{{ route('products.index') }}" class="px-3 py-2 bg-gray-200 rounded">Reset</a>
  </form>

  <div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium">#</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Nama</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Kapasitas</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Deskripsi</th>
          <th class="px-4 py-2 text-right text-sm font-medium">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($products as $p)
          <tr>
            <td class="px-4 py-3 text-sm">{{ $p->id }}</td>
            <td class="px-4 py-3 text-sm">{{ $p->name }}</td>
            <td class="px-4 py-3 text-sm">{{ $p->capacity }}</td>
            <td class="px-4 py-3 text-sm truncate max-w-xl">{{ $p->description ?? '-' }}</td>
            <td class="px-4 py-3 text-sm text-right space-x-1">
              {{-- ✅ Fixed: safely encoded JSON --}}
              @php
                $jsonPayload = htmlspecialchars(json_encode([
                  'id' => $p->id,
                  'name' => $p->name,
                  'capacity' => $p->capacity,
                  'description' => $p->description,
                ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
              @endphp

              <button 
                onclick="openProductModal({!! $jsonPayload !!})"
                class="px-2 py-1 bg-indigo-600 text-white rounded text-xs">
                Detail
              </button>

              <a href="{{ route('products.edit', $p) }}" class="px-2 py-1 bg-yellow-400 text-white rounded text-xs">Edit</a>

              <form action="{{ route('products.destroy', $p) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus product ini?')">
                @csrf @method('DELETE')
                <button class="px-2 py-1 bg-red-600 text-white rounded text-xs">Hapus</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="p-4 text-center text-gray-500">Belum ada product.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $products->links() }}
    </div>
  </div>
</div>

{{-- Modal product detail --}}
<div x-data="productModal()" x-init="init()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded shadow-lg max-w-lg w-full p-4 z-50">
    <div class="flex items-start justify-between">
      <h3 class="text-lg font-medium">Product #<span x-text="payload.id"></span> — <span x-text="payload.name"></span></h3>
      <button @click="close()" class="text-gray-500">✕</button>
    </div>

    <div class="mt-3 text-sm">
      <div><strong>Kapasitas:</strong> <span x-text="payload.capacity"></span></div>
      <div class="mt-2"><strong>Deskripsi:</strong>
        <div class="mt-1 text-gray-700" x-text="payload.description || '-'"></div>
      </div>
    </div>

    <div class="mt-4 flex items-center justify-end">
      <button @click="close()" class="px-3 py-2 bg-gray-200 rounded">Close</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function productModal() {
    return {
      open: false,
      payload: {},
      init() {
        window.addEventListener('open-product-modal', (e) => {
          this.payload = e.detail;
          this.open = true;
        });
      },
      close() {
        this.open = false;
        this.payload = {};
      }
    }
  }

  function openProductModal(data) {
    const evt = new CustomEvent('open-product-modal', { detail: data });
    window.dispatchEvent(evt);
  }
</script>
@endpush
@endsection
