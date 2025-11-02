<?php

namespace App\Http\Controllers;

use App\Events\NewAssignmentCreated; // uncomment jika pakai broadcasting
use App\Models\Assignment;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignmentController extends Controller
{
    /**
     * Menampilkan daftar assignment (untuk staff / admin)
     */
    public function index(Request $request)
    {
        try {
            $q = Assignment::with(['order.product', 'driver', 'guide', 'assignedBy']);

            // Optional filter dari query string (filter nanti di view)
            if ($request->filled('status')) {
                $q->where('status', $request->query('status'));
            }
            if ($request->filled('driver_id')) {
                $q->where('driver_id', $request->query('driver_id'));
            }
            if ($request->filled('guide_id')) {
                $q->where('guide_id', $request->query('guide_id'));
            }
            if ($request->filled('from')) {
                $q->whereDate('assigned_at', '>=', $request->query('from'));
            }
            if ($request->filled('to')) {
                $q->whereDate('assigned_at', '<=', $request->query('to'));
            }

            $assignments = $q->orderBy('assigned_at', 'desc')->paginate(25)->withQueryString();

            return view('assignments.index', compact('assignments'));
        } catch (\Throwable $e) {
            Log::error('Assignment.index error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengambil data assignment.');
        }
    }

    /**
     * Form create assignment
     */
    public function create(Request $request)
    {
        try {
            $orderId = $request->query('order');
            $orders = Order::whereIn('status', ['pending', 'assigned'])->orderBy('pickup_time')->get();
            $drivers = User::where('role', 'driver')->orderBy('name')->get();
            $guides = User::where('role', 'guide')->orderBy('name')->get();
            $order = $orderId ? Order::find($orderId) : null;

            return view('assignments.create', compact('orders', 'drivers', 'guides', 'order'));
        } catch (\Throwable $e) {
            Log::error('Assignment.create error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal membuka form assignment.');
        }
    }

    /**
     * Store assignment (dibuat oleh staff)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id'  => 'required|exists:orders,id',
            'driver_id' => 'required|exists:users,id',
            'guide_id'  => 'nullable|exists:users,id',
            'note'      => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Lock order row to prevent concurrent assigns
            $order = Order::lockForUpdate()->findOrFail($data['order_id']);

            if ($order->status === 'completed') {
                DB::rollBack();
                return redirect()->back()->withInput()->with('error', 'Order sudah selesai, tidak bisa di-assign.');
            }

            $driver = User::findOrFail($data['driver_id']);
            $guide  = $data['guide_id'] ? User::find($data['guide_id']) : null;

            $estHours = $this->calculateEstimatedHours($order);

            $month = now()->month;
            $year  = now()->year;

            // Ambil atau buat work schedule untuk driver & guide
            $driverSchedule = WorkSchedule::firstOrCreate(
                ['user_id' => $driver->id, 'month' => $month, 'year' => $year],
                ['total_hours' => $driver->monthly_work_limit ?? 200, 'used_hours' => 0]
            );

            if (($driverSchedule->used_hours + $estHours) > $driverSchedule->total_hours) {
                DB::rollBack();
                return redirect()->back()->withInput()->with('error', 'Driver tidak memiliki cukup jam kerja bulan ini.');
            }

            if ($guide) {
                $guideSchedule = WorkSchedule::firstOrCreate(
                    ['user_id' => $guide->id, 'month' => $month, 'year' => $year],
                    ['total_hours' => $guide->monthly_work_limit ?? 200, 'used_hours' => 0]
                );

                if (($guideSchedule->used_hours + $estHours) > $guideSchedule->total_hours) {
                    DB::rollBack();
                    return redirect()->back()->withInput()->with('error', 'Guide tidak memiliki cukup jam kerja bulan ini.');
                }
            }

            // Buat assignment
            $assignment = Assignment::create([
                'order_id'     => $order->id,
                'driver_id'    => $driver->id,
                'guide_id'     => $guide?->id,
                'assigned_by'  => Auth::id(),
                'status'       => 'pending',
                'assigned_at'  => now(),
                'note'         => $data['note'] ?? null,
            ]);

            // Update order status menjadi assigned jika perlu
            $order->update(['status' => 'assigned']);

            DB::commit();

            // Broadcast / notifikasi (opsional)
            // event(new NewAssignmentCreated($assignment));

            return redirect()->route('assignments.index')->with('success', 'Assignment berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Assignment.store error: '.$e->getMessage(), [
                'payload' => $data,
                'trace'   => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat membuat assignment.');
        }
    }

    /**
     * Tampilkan detail assignment
     */
    public function show(Assignment $assignment)
    {
        try {
            $assignment->load(['order.product', 'driver', 'guide', 'assignedBy']);
            return view('assignments.show', compact('assignment'));
        } catch (\Throwable $e) {
            Log::error('Assignment.show error: '.$e->getMessage(), ['assignment_id' => $assignment->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal membuka detail assignment.');
        }
    }

    /**
     * Hapus assignment
     */
    public function destroy(Assignment $assignment)
    {
        try {
            $assignment->delete();
            return redirect()->route('assignments.index')->with('success', 'Assignment dihapus.');
        } catch (\Throwable $e) {
            Log::error('Assignment.destroy error: '.$e->getMessage(), ['assignment_id' => $assignment->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal menghapus assignment.');
        }
    }

    /**
     * Daftar tugas user (driver/guide)
     */
    public function myAssignments()
    {
        try {
            $user = Auth::user();

            $assignments = Assignment::where(function ($q) use ($user) {
                if ($user->role === 'driver') {
                    $q->where('driver_id', $user->id);
                } elseif ($user->role === 'guide') {
                    $q->where('guide_id', $user->id);
                }
            })->with(['order.product'])->orderBy('assigned_at', 'desc')->get();

            return view('assignments.my', compact('assignments'));
        } catch (\Throwable $e) {
            Log::error('Assignment.myAssignments error: '.$e->getMessage(), ['user_id' => Auth::id(), 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal mengambil daftar tugas Anda.');
        }
    }

    /**
     * Ubah status assignment oleh driver/guide: accepted, declined, completed
     * Digunakan untuk menggantikan accept() / decline() terpisah agar logika terpusat.
     *
     * Note: tidak ada variabel/cek bernama "admin_user" di controller ini.
     */
    public function changeStatus(Request $request, Assignment $assignment)
    {
        $request->validate([
            'status' => 'required|in:accepted,declined,completed',
        ]);

        DB::beginTransaction();

        try {
            $user = Auth::user();

            // Akses control: hanya driver/guide terkait yang bisa ubah status
            $isDriver = $user->role === 'driver' && $assignment->driver_id === $user->id;
            $isGuide  = $user->role === 'guide' && $assignment->guide_id === $user->id;

            if (! $isDriver && ! $isGuide) {
                abort(403, 'Unauthorized');
            }

            $newStatus = $request->post('status');

            // Jika accepted -> cek jam kerja dan tambahkan used_hours
            if ($newStatus === 'accepted') {
                $estHours = $this->calculateEstimatedHours($assignment->order);

                $ws = WorkSchedule::firstOrCreate(
                    ['user_id' => $user->id, 'month' => now()->month, 'year' => now()->year],
                    ['total_hours' => $user->monthly_work_limit ?? 200, 'used_hours' => 0]
                );

                if (($ws->used_hours + $estHours) > $ws->total_hours) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Tidak cukup jam kerja tersisa untuk menerima tugas ini.');
                }

                $ws->used_hours += $estHours;
                $ws->save();
            }

            // Simpan status assignment
            $assignment->status = $newStatus;
            $assignment->save();

            // Jika completed -> set order completed
            if ($newStatus === 'completed') {
                $assignment->order->update(['status' => 'completed']);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Status tugas diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Assignment.changeStatus error: '.$e->getMessage(), [
                'assignment_id' => $assignment->id,
                'user_id'       => Auth::id(),
                'payload'       => $request->all(),
                'trace'         => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Gagal memperbarui status tugas.');
        }
    }

    /**
     * Utility: hitung estimasi jam dari order (menjadi integer jam, dibulatkan ke atas)
     */
    protected function calculateEstimatedHours(Order $order): int
    {
        // prefer estimated_duration_minutes jika ada, fallback ke difference pickup - arrival
        $minutes = (int) ($order->estimated_duration_minutes ?? 0);

        if ($minutes <= 0 && $order->pickup_time && $order->arrival_time) {
            try {
                $start = strtotime($order->pickup_time);
                $end   = strtotime($order->arrival_time);
                $diff  = max(0, $end - $start);
                $minutes = (int) round($diff / 60);
            } catch (\Throwable $e) {
                Log::warning('calculateEstimatedHours fallback parse error: '.$e->getMessage(), ['order_id' => $order->id]);
                $minutes = 60;
            }
        }

        if ($minutes <= 0) $minutes = 60; // default 1 jam jika tidak ada data

        return (int) ceil($minutes / 60);
    }
}
