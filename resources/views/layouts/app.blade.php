<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>@yield('title', 'Dashboard')</title>

  {{-- Alpine & Tailwind CDN (sederhana) --}}
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  @vite('resources/css/app.css')

  @stack('head')
  <style>
    /* kecil: scrollbar sidebar agar enak di desktop */
    .sidebar-scroll::-webkit-scrollbar { width: 8px; }
    .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); border-radius: 8px; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800" x-data="layout()">
  {{-- If user is authenticated show full dashboard with sidebar/topbar, otherwise show simple centered content (auth pages) --}}
  @if(auth()->check())
    <div class="min-h-screen flex">
      {{-- Sidebar --}}
      <aside class="hidden md:flex md:flex-col w-64 bg-white border-r" :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}" x-cloak>
        @include('partials.sidebar')
      </aside>

      {{-- Mobile overlay sidebar --}}
      <div
        x-show="sidebarOpen"
        x-cloak
        class="fixed inset-0 bg-black/40 z-40 md:hidden"
        @click="sidebarOpen = false"
        ></div>

      <div class="flex-1 flex flex-col min-h-screen">
        {{-- Topbar --}}
        @include('partials.topbar')

        {{-- Main content area --}}
        <main class="p-4">
          @yield('content')
        </main>
      </div>
    </div>
  @else
    {{-- Simple layout for guest (auth pages) --}}
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
      }
    }
  </script>
</body>
</html>
