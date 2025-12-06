@extends('layouts.app')
@section('title', 'Dashboard - Admin')

@section('content')
@php
    $ordersThisMonth = $ordersThisMonth ?? 0;
    $assignedThisMonth = $assignedThisMonth ?? 0;
    $completedThisMonth = $completedThisMonth ?? 0;
    $activeDrivers = $activeDrivers ?? 0;
    $monthlyOrders = $monthlyOrders ?? [];
    $productDistribution = $productDistribution ?? [];
    $topDrivers = $topDrivers ?? [];
    $month = $month ?? now()->month;
    $year = $year ?? now()->year;
    $availableYears = $availableYears ?? [now()->year];
@endphp

<div class="bg-gray-50 min-h-screen">
  <div class="bg-white border-b shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Dashboard Admin</h1>
        <p class="text-sm text-gray-500">Ringkasan aktivitas sistem</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="text-sm text-gray-600">
          <span class="font-medium">{{ auth()->user()->name }}</span>
          <span class="text-gray-400">•</span>
          <span>{{ ucfirst(auth()->user()->role) }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-6">

    {{-- Filter Bulan & Tahun --}}
    <div class="bg-white rounded-lg shadow p-4">
      <form method="GET" class="flex flex-wrap items-end gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Tahun</label>
          <select name="year" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @foreach($availableYears as $y)
              <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Bulan</label>
          <select name="month" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @for($m = 1; $m <= 12; $m++)
              <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
              </option>
            @endfor
          </select>
        </div>
        <div class="flex items-end">
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Terapkan
          </button>
        </div>
      </form>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center">
          <div class="p-3 bg-blue-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-500">Orders Bulan Ini</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $ordersThisMonth }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center">
          <div class="p-3 bg-yellow-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-500">Assigned</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $assignedThisMonth }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center">
          <div class="p-3 bg-green-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-500">Completed</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $completedThisMonth }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center">
          <div class="p-3 bg-indigo-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-500">Driver/Guide Aktif</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $activeDrivers }}</p>
          </div>
        </div>
      </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      {{-- Monthly Orders & Completed Chart --}}
      <div class="lg:col-span-2 bg-white rounded-lg shadow p-5">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Orders & Completed — 12 Bulan Terakhir</h3>
        </div>
        <div class="h-72">
          <canvas id="monthlyOrdersChart"></canvas>
        </div>
      </div>

      {{-- Product Distribution --}}
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribusi Produk ({{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }})</h3>
        <div class="h-72">
          <canvas id="productPieChart"></canvas>
        </div>
      </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-start">
          <div class="p-3 bg-blue-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <div class="ml-4">
            <h4 class="font-semibold text-gray-900">Kelola Order</h4>
            <p class="text-sm text-gray-600 mt-1">Kelola pesanan pelanggan, ubah status, dan lihat detail.</p>
            <div class="mt-3">
              <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Buka Order
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-start">
          <div class="p-3 bg-green-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div class="ml-4">
            <h4 class="font-semibold text-gray-900">Kelola Assignment</h4>
            <p class="text-sm text-gray-600 mt-1">Tugaskan order ke driver/guide, pantau status pengerjaan.</p>
            <div class="mt-3">
              <a href="{{ route('assignments.index') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                Kelola Assignment
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Top Drivers --}}
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Driver/Guide (Jam Kerja Digunakan)</h3>
      @if(!empty($topDrivers))
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          @foreach($topDrivers as $driver)
            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
              <div class="font-medium text-gray-900">{{ $driver['name'] }}</div>
              <div class="mt-2 flex items-center">
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $driver['total_hours'] ? round(($driver['used_hours'] / $driver['total_hours']) * 100) : 0 }}%"></div>
                </div>
              </div>
              <div class="mt-1 text-xs text-gray-600">
                {{ $driver['used_hours'] }} / {{ $driver['total_hours'] }} jam
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="text-center py-8 text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.331 0-4.303-.988-5.828-2.587" />
          </svg>
          <p class="mt-2">Tidak ada data driver/guide untuk bulan ini.</p>
        </div>
      @endif
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // === Monthly Orders & Completed Chart ===
    const monthlyOrders = @json($monthlyOrders);
    const ctx1 = document.getElementById('monthlyOrdersChart');
    if (ctx1) {
      const labels = monthlyOrders.map(i => i.label);
      const orderData = monthlyOrders.map(i => i.orders || 0);
      const completedData = monthlyOrders.map(i => i.completed || 0);

      new Chart(ctx1, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Total Orders',
              data: orderData,
              backgroundColor: 'rgba(59, 130, 246, 0.7)',
              borderColor: 'rgba(59, 130, 246, 1)',
              borderWidth: 1,
              borderRadius: 4,
            },
            {
              label: 'Completed (Driver/Guide)',
              data: completedData,
              backgroundColor: 'rgba(16, 185, 129, 0.7)',
              borderColor: 'rgba(16, 185, 129, 1)',
              borderWidth: 1,
              borderRadius: 4,
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'top' },
            tooltip: { backgroundColor: 'rgba(0,0,0,0.8)' }
          },
          scales: {
            x: {
              grid: { display: false }
            },
            y: {
              beginAtZero: true,
              ticks: { precision: 0 }
            }
          }
        }
      });
    }

    // === Product Pie Chart ===
    const productDist = @json($productDistribution);
    const ctx2 = document.getElementById('productPieChart');
    if (ctx2) {
      const labels = productDist.map(i => i.label);
      const data = productDist.map(i => i.count || 0);

      if (data.length === 0 || data.every(v => v === 0)) {
        const ctx = ctx2.getContext('2d');
        ctx.font = '14px sans-serif';
        ctx.fillStyle = '#9ca3af';
        ctx.textAlign = 'center';
        ctx.fillText('Tidak ada data produk', ctx2.width / 2, ctx2.height / 2);
      } else {
        new Chart(ctx2, {
          type: 'pie',
          data: {
            labels: labels,
            datasets: [{
              data: data,
              backgroundColor: [
                '#3b82f6', '#10b981', '#f59e0b', '#ef4444',
                '#8b5cf6', '#ec4899', '#06b6d4', '#6b7280'
              ],
              borderWidth: 2,
              borderColor: '#fff'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom' },
              tooltip: { backgroundColor: 'rgba(0,0,0,0.8)' }
            }
          }
        });
      }
    }
  });
</script>
@endpush
@endsection