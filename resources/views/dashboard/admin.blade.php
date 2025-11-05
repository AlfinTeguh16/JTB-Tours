@extends('layouts.app')
@section('title','Dashboard - Admin')

@section('content')
@php
  $ordersThisMonth = $ordersThisMonth ?? 0;
  $assignedThisMonth = $assignedThisMonth ?? 0;
  $completedThisMonth = $completedThisMonth ?? 0;
  $activeDrivers = $activeDrivers ?? 0;
  $monthlyOrders = $monthlyOrders ?? [];
  $productDistribution = $productDistribution ?? [];
  $topDrivers = $topDrivers ?? [];
  $month = $month ?? \Carbon\Carbon::now()->month;
  $year = $year ?? \Carbon\Carbon::now()->year;
@endphp

<div class="bg-gray-100">
  <div class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
      <div class="text-xl font-semibold">Dashboard</div>
      <div class="text-sm text-gray-700">Hello, {{ auth()->user()->name }}</div>
    </div>
  </div>

  <div class="max-w-7xl mx-auto px-4 py-6">
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

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
      <div class="lg:col-span-2 bg-white rounded shadow p-4">
        <h3 class="font-semibold mb-2">Orders — 12 months</h3>
        <div class="h-52"><canvas id="monthlyOrdersChart" height="120"></canvas></div>
      </div>

      <div class="bg-white rounded shadow p-4">
        <h3 class="font-semibold mb-2">Product distribution (this month)</h3>
        <div class="h-52"><canvas id="productPieChart" height="200"></canvas></div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="bg-white rounded shadow p-4">
        <h4 class="font-semibold">Orders</h4>
        <p class="text-sm text-gray-600 mt-2">Manage customer orders.</p>
        <div class="mt-3"><a href="{{ Route::has('orders.index') ? route('orders.index') : '#' }}" class="px-3 py-2 bg-blue-600 text-white rounded">Open Orders</a></div>
      </div>

      <div class="bg-white rounded shadow p-4">
        <h4 class="font-semibold">Assignments</h4>
        <p class="text-sm text-gray-600 mt-2">Assign to drivers/guides.</p>
        <div class="mt-3"><a href="{{ Route::has('assignments.index') ? route('assignments.index') : '#' }}" class="px-3 py-2 bg-green-600 text-white rounded">Manage Assignments</a></div>
      </div>
    </div>

    {{-- Top drivers --}}
    <div class="bg-white rounded shadow p-4 mt-6">
      <h4 class="font-semibold mb-3">Top drivers (by used hours)</h4>
      @if(!empty($topDrivers))
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          @foreach($topDrivers as $d)
            <div class="p-3 border rounded">
              <div class="font-medium">{{ $d['name'] }}</div>
              <div class="text-xs text-gray-500">Used: {{ $d['used_hours'] }} / {{ $d['total_hours'] }}</div>
            </div>
          @endforeach
        </div>
      @else
        <div class="text-sm text-gray-500">No data.</div>
      @endif
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const monthlyOrders = {!! json_encode($monthlyOrders ?? []) !!};
  const productDistribution = {!! json_encode($productDistribution ?? []) !!};

  (function () {
    const el = document.getElementById('monthlyOrdersChart');
    if (!el) return;
    const ctx = el.getContext('2d');
    const labels = (monthlyOrders || []).map(i => i.label);
    const data = (monthlyOrders || []).map(i => i.count || 0);
    new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets:[{ label:'Orders', data, borderWidth:1 }] },
      options: { responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:true } }, plugins:{ legend:{ display:false } } }
    });
  })();

  (function () {
    const el = document.getElementById('productPieChart');
    if (!el) return;
    const ctx = el.getContext('2d');
    const labels = (productDistribution || []).map(i => i.label);
    const data = (productDistribution || []).map(i => i.count || 0);
    if (!data.length || data.every(v=>v===0)) { ctx.font='14px sans-serif'; ctx.fillText('Tidak ada data product untuk bulan ini',10,50); return; }
    new Chart(ctx, { type:'pie', data:{ labels, datasets:[{ data, borderWidth:1 }] }, options:{ responsive:true, maintainAspectRatio:false } });
  })();
</script>
@endpush
@endsection
