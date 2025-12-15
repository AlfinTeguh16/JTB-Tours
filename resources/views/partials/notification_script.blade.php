
@push('scripts')
<script>
    function notificationHandler() {
        return {
            unreadCount: 0,
            hasPermission: false,
            
            async startPolling() {
                // Check Notification Permission
                if ('Notification' in window) {
                    if (Notification.permission === 'granted') {
                        this.hasPermission = true;
                    } else if (Notification.permission !== 'denied') {
                        const permission = await Notification.requestPermission();
                        this.hasPermission = permission === 'granted';
                    }
                }

                this.fetchNotifications();
                setInterval(() => this.fetchNotifications(), 30000); // Poll every 30s
            },

            async fetchNotifications() {
                try {
                    const res = await fetch('{{ route('notifications.fetch') }}');
                    if(res.ok) {
                        const data = await res.json();
                        const prevCount = this.unreadCount;
                        this.unreadCount = data.unread_count;
                        
                        // If new notification detected (count increased), show browser notification
                        if (this.unreadCount > prevCount && this.hasPermission) {
                           // Try to show latest message
                           if(data.latest && data.latest.length > 0) {
                               const latestMsg = data.latest[0].data.message ?? 'Ada notifikasi baru!';
                               new Notification('JTB Tours', {
                                   body: latestMsg,
                                   icon: '{{ asset('img/JTB_logo.png') }}'
                               });
                           }
                        }
                    }
                } catch(e) {
                    console.error('Failed to fetch notifications', e);
                }
            }
        }
    }
</script>
@endpush
