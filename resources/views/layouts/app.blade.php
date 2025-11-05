<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Dashboard')</title>

  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2"></script>

  {{-- Load CSS via Vite (recommended). Jika tidak menggunakan Vite, gantikan dengan link CSS Anda --}}
  @if(app()->environment('local'))
    {{-- In local you may run vite dev server --}}
    @vite('resources/css/app.css')
  @else
    @vite('resources/css/app.css')
  @endif

  @stack('head')

  <style>
    .sidebar-scroll::-webkit-scrollbar { width: 8px; }
    .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); border-radius: 8px; }
    [x-cloak] { display: none !important; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800" x-data="layout()" x-init="init()" @keydown.escape.window="closeSidebar()">
  @if(auth()->check())
    <div class="min-h-screen flex">

      {{-- Desktop Sidebar --}}
      <aside
        class="hidden md:flex md:flex-col w-64 bg-white border-r sidebar-scroll"
        aria-hidden="false"
        role="navigation"
      >
        @include('partials.sidebar')
      </aside>

      {{-- Mobile Sidebar + overlay --}}
      <div class="md:hidden" role="dialog" aria-modal="true" x-cloak>
        <div
          x-show="sidebarOpen"
          x-transition.opacity
          class="fixed inset-0 bg-black/40 z-40"
          @click="closeSidebar()"
          aria-hidden="true"
        ></div>

        <aside
          x-show="sidebarOpen"
          x-transition:enter="transform transition ease-in-out duration-200"
          x-transition:enter-start="-translate-x-full"
          x-transition:enter-end="translate-x-0"
          x-transition:leave="transform transition ease-in-out duration-150"
          x-transition:leave-start="translate-x-0"
          x-transition:leave-end="-translate-x-full"
          class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r sidebar-scroll p-4"
        >
          <div class="flex items-center justify-between mb-4">
            <div class="text-lg font-semibold">Menu</div>
            <button @click="closeSidebar()" aria-label="Tutup menu" class="p-1 rounded hover:bg-gray-100">✕</button>
          </div>

          @include('partials.sidebar')
        </aside>
      </div>

      {{-- Main content --}}
      <div class="flex-1 flex flex-col min-h-screen">
        {{-- Topbar --}}
        @include('partials.topbar')

        {{-- Main content area with centered container --}}
        <main class="p-4">
          <div class="max-w-7xl mx-auto w-full">
            @yield('content')
          </div>
        </main>
      </div>
    </div>
  @else
    {{-- Guest layout --}}
    <div class="min-h-screen flex items-center justify-center">
      <div class="w-full max-w-md p-6">
        @yield('content')
      </div>
    </div>
  @endif

  @stack('scripts')

  <script>
    function layout(){
      return {
        sidebarOpen: false,
        init() {
          // If you want to open the sidebar by default on larger screens, set here.
        },
        openSidebar() {
          this.sidebarOpen = true;
          document.documentElement.style.overflow = 'hidden';
          document.body.style.overflow = 'hidden';
        },
        closeSidebar() {
          this.sidebarOpen = false;
          document.documentElement.style.overflow = '';
          document.body.style.overflow = '';
        },
        toggleSidebar() {
          this.sidebarOpen ? this.closeSidebar() : this.openSidebar();
        }
      }
    }
  </script>
</body>
</html>
