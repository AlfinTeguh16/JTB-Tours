{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
  // fallback jika controller tidak mengirim month/year
  $now = \Carbon\Carbon::now();
  $year = isset($year) ? (int)$year : $now->year;
  $month = isset($month) ? (int)$month : $now->month;

  // fallback untuk data statistik
  $ordersThisMonth = $ordersThisMonth ?? 0;
  $assignedThisMonth = $assignedThisMonth ?? 0;
  $completedThisMonth = $completedThisMonth ?? 0;
  $activeDrivers = $activeDrivers ?? 0;

  $monthlyOrders = $monthlyOrders ?? [];
  $productDistribution = $productDistribution ?? [];
  $personal = $personal ?? [];
  $topDrivers = $topDrivers ?? [];
@endphp

<div class="bg-gray-100">
  <div class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <div class="text-xl font-semibold">Dashboard</div>
        <div class="text-sm text-gray-500">Hello, {{ Auth::user()->name }}</div>
      </div>

      <div class="text-sm text-gray-700">Role: <span class="font-medium">{{ ucfirst(Auth::user()->role) }}</span></div>
    </div>
  </div>

  <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    {{-- Quick stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 bg-white rounded shadow p-4">
        <h3 class="font-semibold mb-2">Orders — 12 months</h3>
        <div class="h-52">
          <canvas id="monthlyOrdersChart" height="120"></canvas>
        </div>
      </div>

      <div class="bg-white rounded shadow p-4">
        <h3 class="font-semibold mb-2">Product distribution (this month)</h3>
        <div class="h-52">
          <canvas id="productPieChart" height="200"></canvas>
        </div>
      </div>
    </div>

    {{-- Role panels --}}
    @php $role = Auth::user()->role; @endphp

    @if($role === 'super_admin')
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded shadow p-4">
          <h4 class="font-semibold">Manage Users</h4>
          <p class="text-sm text-gray-600 mt-2">Create/edit users and roles.</p>
          <div class="mt-3"><a href="{{ Route::has('users.index') ? route('users.index') : '#' }}" class="px-3 py-2 bg-blue-600 text-white rounded">Users</a></div>
        </div>

        <div class="bg-white rounded shadow p-4">
          <h4 class="font-semibold">Reports</h4>
          <p class="text-sm text-gray-600 mt-2">Export PDF/Excel.</p>
          <div class="mt-3"><a href="{{ Route::has('reports.index') ? route('reports.index') : '#' }}" class="px-3 py-2 bg-indigo-600 text-white rounded">Reports</a></div>
        </div>

        <div class="bg-white rounded shadow p-4">
          <h4 class="font-semibold">System</h4>
          <p class="text-sm text-gray-600 mt-2">Products & Vehicles</p>
          <div class="mt-3 flex gap-2">
            <a href="{{ Route::has('products.index') ? route('products.index') : '#' }}" class="px-3 py-2 bg-gray-800 text-white rounded">Products</a>
            <a href="{{ Route::has('vehicles.index') ? route('vehicles.index') : '#' }}" class="px-3 py-2 bg-gray-800 text-white rounded">Vehicles</a>
          </div>
        </div>
      </div>
    @endif

    @if($role === 'admin')
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
    @endif

    @if($role === 'staff')
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded shadow p-4">
          <h4 class="font-semibold">Assign Tasks</h4>
          <p class="text-sm text-gray-600 mt-2">Assign orders considering work schedules.</p>
          <div class="mt-3"><a href="{{ Route::has('assignments.create') ? route('assignments.create') : '#' }}" class="px-3 py-2 bg-green-600 text-white rounded">Assign Now</a></div>
        </div>

        <div class="bg-white rounded shadow p-4">
          <h4 class="font-semibold">Driver Availability</h4>
          <p class="text-sm text-gray-600 mt-2">See who is online.</p>
          <div class="mt-3"><a href="{{ Route::has('availability.index') ? route('availability.index') : '#' }}" class="px-3 py-2 bg-gray-800 text-white rounded">Availability</a></div>
        </div>
      </div>
    @endif

    @if(in_array($role, ['driver','guide']))
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded shadow p-4">
          <h4 class="font-semibold">My Assignments</h4>
          <p class="text-sm text-gray-600 mt-2">Tasks assigned to you.</p>
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
    @endif

    {{-- Top drivers --}}
    <div class="bg-white rounded shadow p-4">
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
  // safe JSON from PHP — fallbacks applied in controller/view
  const monthlyOrders = {!! json_encode($monthlyOrders ?? [], JSON_UNESCAPED_UNICODE) !!};
  const productDistribution = {!! json_encode($productDistribution ?? [], JSON_UNESCAPED_UNICODE) !!};

  (function () {
    const el = document.getElementById('monthlyOrdersChart');
    if (!el) return;
    const ctx = el.getContext('2d');
    const labels = (monthlyOrders || []).map(i => i.label);
    const data = (monthlyOrders || []).map(i => i.count || 0);

    new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets: [{ label: 'Orders', data, borderWidth: 1 }] },
      options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
    });
  })();

  (function () {
    const el = document.getElementById('productPieChart');
    if (!el) return;
    const ctx = el.getContext('2d');
    const labels = (productDistribution || []).map(p => p.label);
    const data = (productDistribution || []).map(p => p.count || 0);

    if (!data.length || data.every(v => v === 0)) {
      ctx.font = '14px sans-serif';
      ctx.fillText('Tidak ada data product untuk bulan ini', 10, 50);
      return;
    }

    new Chart(ctx, {
      type: 'pie',
      data: { labels, datasets: [{ data, borderWidth: 1 }] },
      options: { responsive: true, maintainAspectRatio: false }
    });
  })();
</script>
@endpush

@endsection
