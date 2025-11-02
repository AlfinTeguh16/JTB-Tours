@extends('layouts.app')

@section('title','Laporan - JTB Tours')

@section('content')
<div class="container mx-auto">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold">Laporan</h1>

    <div class="flex space-x-2">
      <form method="GET" action="{{ route('reports.index') }}" class="flex items-center space-x-2">
        <input type="number" name="year" value="{{ $year }}" class="border p-2 rounded w-28" />
        <select name="month" class="border p-2 rounded">
          <option value="">All months</option>
          @for($m=1;$m<=12;$m++)
            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $m }}</option>
          @endfor
        </select>
        <button class="bg-blue-600 text-white px-3 py-2 rounded">Filter</button>
      </form>

      <a href="{{ route('reports.export.excel', ['year'=>$year, 'month'=>$month]) }}" class="bg-green-600 text-white px-3 py-2 rounded">Export Excel</a>
      <a href="{{ route('reports.export.pdf', ['year'=>$year, 'month'=>$month]) }}" class="bg-gray-700 text-white px-3 py-2 rounded">Export PDF</a>
    </div>
  </div>

  {{-- Flash --}}
  @if(session('success')) <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div> @endif
  @if(session('error')) <div class="bg-red-100 text-red-800 p-3 rounded mb-4">{{ session('error') }}</div> @endif

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-4 rounded shadow">
      <h3 class="text-lg font-medium mb-2">Jumlah Order per Bulan ({{ $year }})</h3>
      <canvas id="ordersChart" width="400" height="220"></canvas>
    </div>

    <div class="bg-white p-4 rounded shadow">
      <h3 class="text-lg font-medium mb-2">Assignment (Accepted) per Bulan ({{ $year }})</h3>
      <canvas id="assignmentsChart" width="400" height="220"></canvas>
    </div>

    <div class="bg-white p-4 rounded shadow lg:col-span-2">
      <h3 class="text-lg font-medium mb-2">Product Usage (Accepted Assignments) - {{ $year }}</h3>
      <div class="flex items-center space-x-6">
        <canvas id="productPie" width="200" height="200"></canvas>
        <div class="flex-1">
          <ul id="productLegend" class="text-sm"></ul>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  // data from server (PHP -> JS)
  const ordersPerMonth = {!! json_encode(array_values($ordersPerMonth)) !!}; // index 0..11
  const acceptedPerMonth = {!! json_encode(array_values($acceptedPerMonth)) !!};
  const productUsage = {!! json_encode($productUsage->map(function($p){ return ['name'=>$p->name,'total'=> (int)$p->total]; })->values()) !!};

  // labels (bulan)
  const monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  // ORDERS line chart
  const ctxOrders = document.getElementById('ordersChart').getContext('2d');
  new Chart(ctxOrders, {
    type: 'line',
    data: {
      labels: monthLabels,
      datasets: [{
        label: 'Jumlah Order',
        data: ordersPerMonth,
        fill: false,
        tension: 0.2,
        borderWidth: 2,
        pointRadius: 4,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, ticks: { precision:0 } }
      }
    }
  });

  // ASSIGNMENTS line chart
  const ctxAssign = document.getElementById('assignmentsChart').getContext('2d');
  new Chart(ctxAssign, {
    type: 'line',
    data: {
      labels: monthLabels,
      datasets: [{
        label: 'Accepted Assignments',
        data: acceptedPerMonth,
        fill: false,
        tension: 0.2,
        borderWidth: 2,
        pointRadius: 4,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, ticks: { precision:0 } }
      }
    }
  });

  // PRODUCT PIE chart
  const pieCtx = document.getElementById('productPie').getContext('2d');
  const productLabels = productUsage.map(p => p.name);
  const productData = productUsage.map(p => p.total);

  const productPie = new Chart(pieCtx, {
    type: 'pie',
    data: {
      labels: productLabels,
      datasets: [{
        data: productData,
        borderWidth: 1,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false } // we render custom legend
      }
    }
  });

  // render legend
  const legendEl = document.getElementById('productLegend');
  productUsage.forEach((p, idx) => {
    const li = document.createElement('li');
    li.className = 'mb-2';
    const color = productPie.data.datasets[0].backgroundColor?.[idx] || '#ccc';
    // small color box
    const box = document.createElement('span');
    box.style.display = 'inline-block';
    box.style.width = '12px';
    box.style.height = '12px';
    box.style.background = productPie.data.datasets[0].backgroundColor?.[idx] || '#999';
    box.style.marginRight = '8px';
    box.style.verticalAlign = 'middle';
    li.appendChild(box);

    const text = document.createTextNode(`${p.name} — ${p.total}`);
    li.appendChild(text);
    legendEl.appendChild(li);
  });
</script>
@endsection
