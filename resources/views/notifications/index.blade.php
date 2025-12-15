@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="max-w-4xl mx-auto p-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Notifikasi</h1>
        
        <form action="{{ route('notifications.readAll') }}" method="POST">
            @csrf
            <button type="submit" class="text-sm text-blue-600 hover:text-blue-800">
                Tandai semua dibaca
            </button>
        </form>
    </div>

    <div class="space-y-4">
        @forelse($notifications as $notif)
            @php
                $data = $notif->data;
                $isRead = !is_null($notif->read_at);
                $bgColor = $isRead ? 'bg-white' : 'bg-blue-50 border-l-4 border-blue-500';
            @endphp
            <div class="{{ $bgColor }} p-4 rounded shadow transition hover:shadow-md">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-900 {{ $isRead ? '' : 'font-semibold' }}">
                            {{ $data['message'] ?? 'Notifikasi baru' }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $notif->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @if(isset($data['link']))
                        <a href="{{ route('notifications.read', $notif->id) }}" class="px-3 py-1 bg-gray-100 text-gray-600 rounded text-sm hover:bg-gray-200">
                            Lihat
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-gray-500 bg-white rounded shadow">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <p>Tidak ada notifikasi saat ini.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
