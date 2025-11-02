{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="min-h-screen flex bg-gray-100">
  {{-- SIDEBAR --}}
  <aside class="bg-white border-r w-64 hidden md:block">
    <div class="h-full flex flex-col">
      <div class="px-4 py-6 border-b">
        <h2 class="text-lg font-bold">Admin Panel</h2>
        <div class="text-xs text-gray-500 mt-1">Welcome, {{ Auth::user()->name }}</div>
      </div>

      <nav class="flex-1 p-4 overflow-y-auto space-y-1">
        <a href="{{ route('dashboard') }}" class="block py-2 px-3 rounded hover:bg-gray-50 {{ request()->routeIs('dashboard') ? 'bg-gray-100 font-semibold' : '' }}">Dashboard</a>

        @if(Auth::check() && Auth::user()->role === 'super_admin')
          <div class="mt-3 text-xs text-gray-500 px-3">Super Admin</div>
          <a href="{{ route('users.index') }}" class="block py-2 px-3 rounded hover:bg-gray-50">Manage Users</a>
          <a href="{{ route('reports.index') }}" class="block py-2 px-3 rounded hover:bg-gray-50">Reports</a>
        @endif

        @if(Auth::check() && Auth::user()->role === 'admin')
          <div class="mt-3 text-xs text-gray-500 px-3">Admin</div>
          <a href="{{ route('orders.index') }}" class="block py-2 px-3 rounded hover:bg-gray-50">Orders</a>
          <a href="{{ route('assignments.index') }}" class="block py-2 px-3 rounded hover:bg-gray-50">Assignments</a>
        @endif

        @if(Auth::check() && Auth::user()->role === 'staff')
          <div class="mt-3 text-xs text-gray-500 px-3">Staff</div>
          <a href="{{ route('assignments.create') }}" class="block py-2 px-3 rounded hover:bg-gray-50">Assign Task</a>
        @endif

        @if(Auth::check() && in_array(Auth::user()->role, ['driver','guide']))
          <div class="mt-3 text-xs text-gray-500 px-3">My Area</div>
          <a href="{{ route('assignments.my') }}" class="block py-2 px-3 rounded hover:bg-gray-50">My Assignments</a>
          <a href="{{ route('work-schedules.index') }}" class="block py-2 px-3 rounded hover:bg-gray-50">My Schedule</a>
        @endif

        <div class="mt-6 border-t pt-3">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 text-red-600">Logout</button>
          </form>
        </div>
      </nav>
    </div>
  </aside>

  {{-- MAIN CONTENT --}}
  <div class="flex-1 flex flex-col">
    {{-- TOPBAR --}}
    <header class="bg-white border-b">
      <div class="flex items-center justify-between px-4 py-3">
        <div class="flex items-center gap-3">
          <h1 class="text-lg font-semibold">Dashboard</h1>
          <div class="text-sm text-gray-500">Hello, {{ Auth::user()->name }}</div>
        </div>

        <div class="flex items-center gap-4">
          <div class="text-sm text-gray-700">Role: <span class="font-medium">{{ ucfirst(Auth::user()->role) }}</span></div>
        </div>
      </div>
    </header>

    {{-- BODY --}}
    <main class="p-6">
      {{-- Quick stats --}}
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded shadow p-4">
          <div class="text-sm text-gray-500">Orders ({{ \Carbon\Carbon::create($year,$month,1)->format('M Y') }})</div>
          <div class="text-2xl font-semibold">{{ $ordersThisMonth ?? 0 }}</div>
        </div>

        <div class="bg-white rounded shadow p-4">
          <div class="text-sm text-gray-500">Assigned</div>
          <div class="text-2xl font-semibold">{{ $assignedThisMonth ?? 0 }}</div>
        </div>

        <div class="bg-white rounded shadow p-4">
          <div class="text-sm text-gray-500">Completed</div>
          <div class="text-2xl font-semibold">{{ $completedThisMonth ?? 0 }}</div>
        </div>

        <div class="bg-white rounded shadow p-4">
          <div class="text-sm text-gray-500">Active Drivers/Guides</div>
          <div class="text-2xl font-semibold">{{ $activeDrivers ?? 0 }}</div>
        </div>
      </div>

      {{-- Charts --}}
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="col-span-2 bg-white rounded shadow p-4">
          <h3 class="font-semibold mb-2">Orders — 12 months</h3>
          <canvas id="monthlyOrdersChart" height="120"></canvas>
        </div>

        <div class="bg-white rounded shadow p-4">
          <h3 class="font-semibold mb-2">Product distribution (this month)</h3>
          <canvas id="productPieChart" height="200"></canvas>
        </div>
      </div>

      {{-- Role specific panels --}}
      @php $role = Auth::user()->role; @endphp

      @if($role === 'super_admin')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
          <div class="bg-white rounded shadow p-4">
            <h4 class="font-semibold">Manage Users</h4>
            <p class="text-sm text-gray-600 mt-2">Create/edit users and roles.</p>
            <div class="mt-3"><a href="{{ route('users.index') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Users</a></div>
          </div>

          <div class="bg-white rounded shadow p-4">
            <h4 class="font-semibold">Reports</h4>
            <p class="text-sm text-gray-600 mt-2">Export PDF/Excel.</p>
            <div class="mt-3"><a href="{{ route('reports.index') }}" class="px-3 py-2 bg-indigo-600 text-white rounded">Reports</a></div>
          </div>

          <div class="bg-white rounded shadow p-4">
            <h4 class="font-semibold">System</h4>
            <p class="text-sm text-gray-600 mt-2">Products & Vehicles</p>
            <div class="mt-3 flex gap-2">
              <a href="{{ route('products.index') }}" class="px-3 py-2 bg-gray-800 text-white rounded">Products</a>
              <a href="{{ route('vehicles.index') }}" class="px-3 py-2 bg-gray-800 text-white rounded">Vehicles</a>
            </div>
          </div>
        </div>
      @endif

      @if($role === 'admin')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
          <div class="bg-white rounded shadow p-4">
            <h4 class="font-semibold">Orders</h4>
            <p class="text-sm text-gray-600 mt-2">Manage customer orders.</p>
            <div class="mt-3"><a href="{{ route('orders.index') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Open Orders</a></div>
          </div>

          <div class="bg-white rounded shadow p-4">
            <h4 class="font-semibold">Assignments</h4>
            <p class="text-sm text-gray-600 mt-2">Assign to drivers/guides.</p>
            <div class="mt-3"><a href="{{ route('assignments.index') }}" class="px-3 py-2 bg-green-600 text-white rounded">Manage Assignments</a></div>
          </div>
        </div>
      @endif

      @if($role === 'staff')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
          <div class="bg-white rounded shadow p-4">
            <h4 class="font-semibold">Assign Tasks</h4>
            <p class="text-sm text-gray-600 mt-2">Assign orders considering work schedules.</p>
            <div class="mt-3"><a href="{{ route('assignments.create') }}" class="px-3 py-2 bg-green-600 text-white rounded">Assign Now</a></div>
          </div>

          <div class="bg-white rounded shadow p-4">
            <h4 class="font-semibold">Driver Availability</h4>
            <p class="text-sm text-gray-600 mt-2">See who is online.</p>
            <div class="mt-3"><a href="{{ route('availability.index') }}" class="px-3 py-2 bg-gray-800 text-white rounded">Availability</a></div>
          </div>
        </div>
      @endif

      @if(in_array($role, ['driver','guide']))
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
          <div class="bg-white rounded shadow p-4">
            <h4 class="font-semibold">My Assignments</h4>
            <p class="text-sm text-gray-600 mt-2">Tasks assigned to you.</p>
            <div class="mt-3"><a href="{{ route('assignments.my') }}" class="px-3 py-2 bg-blue-600 text-white rounded">My Tasks</a></div>
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
    </main>
  </div>
</div>

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  // Prepare data passed from controller — use json_encode to avoid analyzer issues
  const monthlyOrders = JSON.parse('{!! addslashes(json_encode($monthlyOrders, JSON_UNESCAPED_UNICODE|JSON_HEX_APOS)) !!}');
  const productDistribution = JSON.parse('{!! addslashes(json_encode($productDistribution, JSON_UNESCAPED_UNICODE|JSON_HEX_APOS)) !!}');

  // Monthly orders chart
  (function () {
    const ctx = document.getElementById('monthlyOrdersChart').getContext('2d');
    const labels = monthlyOrders.map(i => i.label);
    const data = monthlyOrders.map(i => i.count || 0);

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Orders',
          data,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true }
        },
        plugins: {
          legend: { display: false }
        }
      }
    });
  })();

  // Product pie chart
  (function () {
    const ctx = document.getElementById('productPieChart').getContext('2d');
    const labels = productDistribution.map(p => p.label);
    const data = productDistribution.map(p => p.count || 0);

    // fallback when no data
    if (!data.length) {
      ctx.font = '14px sans-serif';
      ctx.fillText('Tidak ada data product untuk bulan ini', 10, 50);
      return;
    }

    new Chart(ctx, {
      type: 'pie',
      data: {
        labels,
        datasets: [{
          data,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });
  })();
</script>
@endpush

@endsection
