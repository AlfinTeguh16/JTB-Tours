<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index()
    {
        // Mark all as read when opening the page (optimistic "seen" logic)
        // Requirement: "hilangkan ketika sudah dibuka" (hilangkan badge number)
        Auth::user()->unreadNotifications->markAsRead();

        // Get all notifications for current user, paginated
        $notifications = Auth::user()->notifications()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
            
            // Redirect to the link inside notification logic if exists
            if (isset($notification->data['link'])) {
                return redirect($notification->data['link']);
            }
        }
        return back();
    }

    /**
     * Mark all as read.
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
    
    /**
     * Fetch unread count and latest (for simpler Polling UI)
     */
    public function fetchLatest()
    {
        $user = Auth::user();
        $unreadCount = $user->unreadNotifications()->count();
        $latest = $user->unreadNotifications()->latest()->take(5)->get();
        
        return response()->json([
            'unread_count' => $unreadCount,
            'latest' => $latest
        ]);
    }
}
