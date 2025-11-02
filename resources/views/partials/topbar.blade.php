@include('availability.toggle')

<nav class="bg-white border-b">
    <div class="max-w-6xl mx-auto px-4">
      <div class="flex items-center justify-between h-14">
        <div class="flex items-center space-x-3">
          {{-- mobile hamburger --}}
          <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-2 rounded hover:bg-gray-100">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
  
          <a href="{{ url('/') }}" class="flex items-center space-x-2">
            <div class="w-8 h-8 bg-blue-600 text-white rounded flex items-center justify-center font-bold">AP</div>
            <div class="hidden sm:block">
              <div class="text-sm font-semibold">AlphaPartner</div>
              <div class="text-xs text-gray-500">Dashboard</div>
            </div>
          </a>
        </div>

        @php
            $user = auth()->user();
        @endphp

        @if($user && in_array($user->role, ['driver','guide']))
        <div class="hidden md:flex items-center space-x-2">
            <form action="{{ route('availability.toggle') }}" method="POST">
            @csrf
            <button type="submit" class="flex items-center gap-2 px-3 py-1 rounded {{ $user->status === 'online' ? 'bg-green-600 text-white' : 'bg-gray-200' }}">
                <span class="w-2 h-2 rounded-full {{ $user->status === 'online' ? 'bg-white' : 'bg-gray-400' }}"></span>
                <span class="text-sm">{{ $user->status === 'online' ? 'Online' : 'Offline' }}</span>
            </button>
            </form>
        </div>
        @endif

  
        <div class="flex items-center space-x-3">
          {{-- optional search --}}
          <div class="hidden sm:block">
            <form method="GET" action="#" class="relative">
              <input name="q" placeholder="Cari..." class="pl-3 pr-8 py-1 rounded border-gray-200 bg-gray-50 text-sm" />
              <button type="submit" class="absolute right-0 top-0 mt-1 mr-1 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
              </button>
            </form>
          </div>
  
          {{-- notifications placeholder --}}
          <button class="p-2 rounded hover:bg-gray-100" title="Notifications">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118.6 14.6L17 13V8a5 5 0 10-10 0v5l-1.6 1.6c-.3.3-.5.69-.5 1.12V17h14z"/></svg>
          </button>
  
          {{-- user dropdown --}}
          <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center space-x-2 px-2 py-1 rounded hover:bg-gray-100">
              <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center text-sm">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
              <div class="hidden sm:block text-sm">
                <div>{{ auth()->user()->name }}</div>
                <div class="text-xs text-gray-500">{{ auth()->user()->role }}</div>
              </div>
            </button>
  
            <div x-show="open" @click.away="open=false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded shadow z-50">
              <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-50">Profile</a>
              <a href="{{ route('work-schedules.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-50">Work Schedule</a>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50">Logout</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>
  