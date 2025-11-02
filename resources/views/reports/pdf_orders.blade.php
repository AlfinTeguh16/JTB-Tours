<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <title>Orders Report</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size:12px; }
    table { width:100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border:1px solid #ddd; padding:6px; }
    th { background:#f4f4f4; font-weight:600; }
    h3 { margin:0; padding:0; }
    .meta { font-size:11px; color:#555; margin-top:6px; }
  </style>
</head>
<body>
  <h3>Orders Report - {{ $year }} {{ $month ? '- Month '.$month : '' }}</h3>
  <div class="meta">Generated: {{ now()->format('Y-m-d H:i') }}</div>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Pickup</th>
        <th>Arrival</th>
        <th>Product</th>
        <th>Status</th>
        <th>Passengers</th>
      </tr>
    </thead>
    <tbody>
      @foreach($orders as $o)
        <tr>
          <td>{{ $o->id }}</td>
          <td>{{ $o->customer_name }}</td>
          <td>{{ $o->phone }}</td>
          <td>{{ $o->pickup_time?->format('Y-m-d H:i') }}</td>
          <td>{{ $o->arrival_time?->format('Y-m-d H:i') }}</td>
          <td>{{ $o->product?->name }}</td>
          <td>{{ $o->status }}</td>
          <td class="text-center">{{ $o->passengers }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
