@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-6xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Vehicles</h1>
    <a href="{{ route('vehicles.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Tambah Kendaraan</a>
  </div>

  <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-2">
    <div>
      <input name="search" value="{{ request('search') }}" placeholder="Cari brand / tipe / plat" class="mt-1 block w-full rounded border-gray-200 px-3 py-2" />
    </div>
    <div>
      <select name="status" class="mt-1 block w-full rounded border-gray-200 px-3 py-2">
        <option value="">Semua Status</option>
        <option value="available" @if(request('status')=='available') selected @endif>Available</option>
        <option value="in_use" @if(request('status')=='in_use') selected @endif>In Use</option>
        <option value="maintenance" @if(request('status')=='maintenance') selected @endif>Maintenance</option>
      </select>
    </div>
    <div class="flex items-end">
      <button class="px-3 py-2 bg-gray-800 text-white rounded">Filter</button>
      <a href="{{ route('vehicles.index') }}" class="ml-2 px-3 py-2 bg-gray-200 rounded">Reset</a>
    </div>
  </form>

  <div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium">#</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Brand / Type</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Plat</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Capacity</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Year</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Status</th>
          <th class="px-4 py-2 text-right text-sm font-medium">Aksi</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100">
        @forelse($vehicles as $v)
          <tr>
            <td class="px-4 py-3 text-sm">{{ $v->id }}</td>
            <td class="px-4 py-3 text-sm">
              <div class="font-medium">{{ $v->brand }} — {{ $v->type }}</div>
            </td>
            <td class="px-4 py-3 text-sm">{{ $v->plate_number }}</td>
            <td class="px-4 py-3 text-sm">{{ $v->capacity }}</td>
            <td class="px-4 py-3 text-sm">{{ $v->year ?? '-' }}</td>
            <td class="px-4 py-3 text-sm">
              <span class="px-2 py-1 rounded text-xs {{ $v->status === 'available' ? 'bg-green-100 text-green-800' : ($v->status==='in_use' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                {{ ucfirst(str_replace('_',' ', $v->status)) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-right">
              <button onclick="openVehicleModal(@json(['id'=>$v->id,'brand'=>$v->brand,'type'=>$v->type,'plate'=>$v->plate_number,'color'=>$v->color,'capacity'=>$v->capacity,'year'=>$v->year,'status'=>$v->status]))" class="px-2 py-1 bg-indigo-600 text-white rounded text-xs">Detail</button>

              <a href="{{ route('vehicles.edit', $v) }}" class="px-2 py-1 ml-1 bg-yellow-400 text-white rounded text-xs">Edit</a>

              <form action="{{ route('vehicles.destroy', $v) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus kendaraan?')">
                @csrf @method('DELETE')
                <button class="px-2 py-1 ml-1 bg-red-600 text-white rounded text-xs">Hapus</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="p-4 text-center text-gray-500">Belum ada kendaraan.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $vehicles->links() }}
    </div>
  </div>
</div>

{{-- vehicle detail modal --}}
<div x-data="vehicleModal()" x-init="init()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded shadow-lg max-w-lg w-full p-4 z-50">
    <div class="flex items-start justify-between">
      <h3 class="text-lg font-medium">Vehicle #<span x-text="payload.id"></span> — <span x-text="payload.brand"></span></h3>
      <button @click="close()" class="text-gray-500">✕</button>
    </div>

    <div class="mt-3 text-sm space-y-2">
      <div><strong>Type:</strong> <span x-text="payload.type"></span></div>
      <div><strong>Plate:</strong> <span x-text="payload.plate"></span></div>
      <div><strong>Color:</strong> <span x-text="payload.color || '-'"></span></div>
      <div><strong>Capacity:</strong> <span x-text="payload.capacity"></span></div>
      <div><strong>Year:</strong> <span x-text="payload.year || '-'"></span></div>
      <div><strong>Status:</strong> <span x-text="payload.status"></span></div>
    </div>

    <div class="mt-4 flex items-center justify-end">
      <a :href="'/vehicles/' + payload.id + '/edit'" class="px-3 py-2 bg-yellow-400 text-white rounded">Edit</a>
      <button @click="close()" class="ml-2 px-3 py-2 bg-gray-200 rounded">Close</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function vehicleModal(){
    return {
      open:false,
      payload:{},
      init(){
        window.addEventListener('open-vehicle-modal', (e)=> {
          this.payload = e.detail;
          this.open = true;
        });
      },
      close(){ this.open=false; this.payload={}; }
    }
  }

  function openVehicleModal(data){
    const evt = new CustomEvent('open-vehicle-modal', { detail: data });
    window.dispatchEvent(evt);
  }
</script>
@endpush

@endsection
