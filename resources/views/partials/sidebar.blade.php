<div class="h-full sidebar-scroll overflow-y-auto p-4">
    <div class="mb-4">
      <div class="text-xs text-gray-400 uppercase mb-2">Menu</div>
  
      <ul class="space-y-1">
        {{-- Semua user yang login lihat Dashboard --}}
        @if(auth()->check())
          <li>
            <a href="{{ route('dashboard') ?? url('/') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('dashboard') ? 'bg-gray-100 font-semibold' : '' }}">
              <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"/></svg>
              <span class="text-sm">Dashboard</span>
            </a>
          </li>
        @endif
  
        {{-- Users: hanya Super Admin --}}
        @if(auth()->check() && auth()->user()->role === 'super_admin')
          <li>
            <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('users.*') ? 'bg-gray-100 font-semibold' : '' }}">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 11a4 4 0 10-8 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
              <span class="text-sm">Users</span>
            </a>
          </li>
        @endif
  
        {{-- Orders: Admin & Super Admin --}}
        @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin']))
          <li>
            <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('orders.*') ? 'bg-gray-100 font-semibold' : '' }}">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 7h18M3 12h18M3 17h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
              <span class="text-sm">Orders</span>
            </a>
          </li>
        @endif
  
        {{-- Assignments: Staff, Admin, Super Admin; Driver/Guide see "Tugas Saya" instead --}}
        @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin','staff']))
          <li>
            <a href="{{ route('assignments.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('assignments.*') ? 'bg-gray-100 font-semibold' : '' }}">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 17v-6l12-2v8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
              <span class="text-sm">Assignments</span>
            </a>
          </li>
        @endif
  
        {{-- Tugas Saya: only driver & guide --}}
        @if(auth()->check() && in_array(auth()->user()->role, ['driver','guide']))
          <li>
            <a href="{{ route('assignments.my') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('assignments.my') ? 'bg-gray-100 font-semibold' : '' }}">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 17v-6l12-2v8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
              <span class="text-sm">Tugas Saya</span>
            </a>
          </li>
        @endif
  
        {{-- Vehicles: Admin & Super Admin --}}
        @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin']))
          <li>
            <a href="{{ route('vehicles.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('vehicles.*') ? 'bg-gray-100 font-semibold' : '' }}">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 13h18v-4H3z M7 13v4 M17 13v4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
              <span class="text-sm">Vehicles</span>
            </a>
          </li>
        @endif
  
        {{-- Products: Admin & Super Admin --}}
        @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin']))
          <li>
            <a href="{{ route('products.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('products.*') ? 'bg-gray-100 font-semibold' : '' }}">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2l7 4v6l-7 4-7-4V6z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
              <span class="text-sm">Products</span>
            </a>
          </li>
        @endif
  
        {{-- Work Schedules: Super Admin & Admin (Staff mungkin butuh akses melihat) --}}
        @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin','staff']))
          <li>
            <a href="{{ route('work-schedules.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('work-schedules.*') ? 'bg-gray-100 font-semibold' : '' }}">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 7V3 M16 7V3 M3 11h18M5 21h14" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
              <span class="text-sm">Work Schedules</span>
            </a>
          </li>
        @endif
  
        {{-- Reports: Super Admin & Admin --}}
        @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin']))
          <li>
            <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('reports.*') ? 'bg-gray-100 font-semibold' : '' }}">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3v18h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
              <span class="text-sm">Reports</span>
            </a>
          </li>
        @endif
      </ul>
    </div>
  
    <div class="mt-6 pt-6 border-t">
      <div class="text-xs text-gray-400 uppercase mb-2 px-3">Settings</div>
      <ul class="space-y-1 px-3">
        @if(auth()->check())
          <li>
            <a href="#" class="block px-3 py-2 rounded hover:bg-gray-50 text-sm">Profile</a>
          </li>
  
          {{-- Work schedule quick link for driver/guide --}}
          @if(in_array(auth()->user()->role, ['driver','guide']))
            <li>
              <a href="{{ route('work-schedules.index') }}" class="block px-3 py-2 rounded hover:bg-gray-50 text-sm">Jam Kerja Saya</a>
            </li>
          @endif
  
          {{-- Admin-only preference --}}
          @if(auth()->user()->role === 'super_admin')
            <li>
              <a href="#" class="block px-3 py-2 rounded hover:bg-gray-50 text-sm">System Settings</a>
            </li>
          @endif
        @endif
      </ul>
    </div>
  </div>
  