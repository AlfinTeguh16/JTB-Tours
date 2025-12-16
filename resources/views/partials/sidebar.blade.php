@php
  $isAuth = auth()->check();
  $role = $isAuth ? auth()->user()->role : null;
@endphp

<div class="h-full sidebar-scroll overflow-y-auto p-4">
  <div class="mb-4">
    <div class="text-xs text-gray-400 uppercase mb-2">Menu</div>

    <ul class="space-y-1">
      
      @if($isAuth)
        <li>
          <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}"
             class="flex items-center gap-3 px-3 py-2 rounded hover:bg-red-100 {{ request()->routeIs('dashboard') ? 'bg-red-100 font-semibold' : '' }}">
            <i class="ph ph-house text-gray-600 text-base" aria-hidden="true"></i>
            <span class="text-sm">Dashboard</span>
          </a>
        </li>
      @endif

      
      @if($isAuth && $role === 'super_admin')
        <li>
          <a href="{{ Route::has('users.index') ? route('users.index') : '#' }}"
             class="flex items-center gap-3 px-3 py-2 rounded hover:bg-red-100 {{ request()->routeIs('users.*') ? 'bg-red-100 font-semibold' : '' }}">
            <i class="ph ph-users text-gray-600 text-base" aria-hidden="true"></i>
            <span class="text-sm">Users</span>
          </a>
        </li>
      @endif

      
      @if($isAuth && in_array($role, ['super_admin','admin','staff']))
        <li>
          <a href="{{ Route::has('orders.index') ? route('orders.index') : '#' }}"
             class="flex items-center gap-3 px-3 py-2 rounded hover:bg-red-100 {{ request()->routeIs('orders.*') ? 'bg-red-100 font-semibold' : '' }}">
            <i class="ph ph-list text-gray-600 text-base" aria-hidden="true"></i>
            <span class="text-sm">Orders</span>
          </a>
        </li>
      @endif

      
      @if($isAuth && in_array($role, ['super_admin','staff']))
        <li>
          <a href="{{ Route::has('assignments.index') ? route('assignments.index') : '#' }}"
             class="flex items-center gap-3 px-3 py-2 rounded hover:bg-red-100 {{ request()->routeIs('assignments.index','assignments.create') ? 'bg-red-100 font-semibold' : '' }}">
            <i class="ph ph-clipboard-text text-gray-600 text-base" aria-hidden="true"></i>
            <span class="text-sm">Assignments</span>
          </a>
        </li>
      @endif

      
      @if($isAuth && in_array($role, ['driver','guide']))
        <li>
          <a href="{{ Route::has('assignments.my') ? route('assignments.my') : '#' }}"
             class="flex items-center gap-3 px-3 py-2 rounded hover:bg-red-100 {{ request()->routeIs('assignments.my') ? 'bg-red-100 font-semibold' : '' }}">
            <i class="ph ph-clipboard text-gray-600 text-base" aria-hidden="true"></i>
            <span class="text-sm">Tugas Saya</span>
          </a>
        </li>
      @endif

      
      @if($isAuth && in_array($role, ['super_admin','staff']))
        <li>
          <a href="{{ Route::has('vehicles.index') ? route('vehicles.index') : '#' }}"
             class="flex items-center gap-3 px-3 py-2 rounded hover:bg-red-100 {{ request()->routeIs('vehicles.*') ? 'bg-red-100 font-semibold' : '' }}">
            <i class="ph ph-car text-gray-600 text-base" aria-hidden="true"></i>
            <span class="text-sm">Vehicles</span>
          </a>
        </li>
      @endif

      
      @if($isAuth && in_array($role, ['super_admin','staff']))
        <li>
          <a href="{{ Route::has('products.index') ? route('products.index') : '#' }}"
             class="flex items-center gap-3 px-3 py-2 rounded hover:bg-red-100 {{ request()->routeIs('products.*') ? 'bg-red-100 font-semibold' : '' }}">
            <i class="ph ph-package text-gray-600 text-base" aria-hidden="true"></i>
            <span class="text-sm">Products</span>
          </a>
        </li>
      @endif

      
      @if($isAuth && in_array($role, ['super_admin','staff']))
        <li>
          <a href="{{ Route::has('work-schedules.index') ? route('work-schedules.index') : '#' }}"
             class="flex items-center gap-3 px-3 py-2 rounded hover:bg-red-100 {{ request()->routeIs('work-schedules.*') ? 'bg-red-100 font-semibold' : '' }}">
            <i class="ph ph-calendar-check text-gray-600 text-base" aria-hidden="true"></i>
            <span class="text-sm">Work Schedules</span>
          </a>
        </li>
      @endif

      
      @if($isAuth && in_array($role, ['super_admin','admin','staff','driver','guide']))
        <li>
          <a href="{{ Route::has('reports.index') ? route('reports.index') : '#' }}"
             class="flex items-center gap-3 px-3 py-2 rounded hover:bg-red-100 {{ request()->routeIs('reports.*') ? 'bg-red-100 font-semibold' : '' }}">
            <i class="ph ph-chart-bar text-gray-600 text-base" aria-hidden="true"></i>
            <span class="text-sm">Reports</span>
          </a>
        </li>
      @endif

    </ul>
  </div>
</div>
