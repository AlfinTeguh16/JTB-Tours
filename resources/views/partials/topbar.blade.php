<nav class="bg-white border-b">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-center justify-between h-14">
            <div class="flex items-center space-x-3">
              
              <div class="md:hidden">
                <button 
                    @click="sidebarOpen = !sidebarOpen" 
                    class="flex items-center p-2 rounded hover:bg-gray-100 md:hidden"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
              </div>

                <a href="{{ url('/dashboard') }}" class="flex items-center space-x-2">
                    <img src="{{ asset('img/JTB_logo.png') }}" alt="" class="max-w-[50px]">
                </a>
            </div>

            @php
                $user = auth()->user();
            @endphp

            @if($user && in_array($user->role, ['driver','guide']))
                <div class="hidden md:flex items-center space-x-2">
                    <form action="{{ route('availability.toggle') }}" method="POST">
                        @csrf
                        <button 
                            type="submit" 
                            class="flex items-center gap-2 px-3 py-1 rounded {{ $user->status === 'online' ? 'bg-green-600 text-white' : 'bg-gray-200' }}"
                        >
                            <span class="w-2 h-2 rounded-full {{ $user->status === 'online' ? 'bg-white' : 'bg-gray-400' }}"></span>
                            <span class="text-sm">{{ $user->status === 'online' ? 'Online' : 'Offline' }}</span>
                        </button>
                    </form>
                </div>
            @endif

            <div class="flex items-center space-x-3">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center space-x-2 px-2 py-1 rounded hover:bg-gray-100">
                        <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center text-sm">
                            {{ strtoupper(substr(auth()->user()->name,0,1)) }}
                        </div>
                        <div class="hidden sm:block text-sm">
                            <div>{{ auth()->user()->name }}</div>
                            <div class="text-xs text-gray-500">{{ auth()->user()->role }}</div>
                        </div>
                    </button>

                    <div 
                        x-show="open" 
                        @click.away="open=false" 
                        x-cloak 
                        class="absolute right-0 mt-2 w-48 bg-white rounded shadow z-50"
                    >
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-gray-50">
                            Profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button 
                                type="submit" 
                                class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50"
                            >
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</nav>
