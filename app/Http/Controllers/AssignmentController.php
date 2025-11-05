<?php

namespace App\Http\Controllers;

use App\Events\NewAssignmentCreated;
use App\Models\Assignment;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    /**
     * Menampilkan daftar assignment (untuk staff / admin)
     */
    public function index(Request $request)
    {
        try {
            $q = Assignment::with(['order.product', 'driver', 'guide', 'assignedBy']);

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
            Log::error('Assignment.index error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
            Log::error('Assignment.create error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
            $order = Order::lockForUpdate()->findOrFail($data['order_id']);

            if ($order->status === 'completed') {
                DB::rollBack();
                return redirect()->back()->withInput()->with('error', 'Order sudah selesai, tidak bisa di-assign.');
            }

            $driver = User::findOrFail($data['driver_id']);
            $guide = $data['guide_id'] ? User::find($data['guide_id']) : null;

            $estHours = $this->calculateEstimatedHours($order);
            $month = now()->month;
            $year = now()->year;

            // Work schedule driver
            $driverSchedule = WorkSchedule::firstOrCreate(
                ['user_id' => $driver->id, 'month' => $month, 'year' => $year],
                ['total_hours' => $driver->monthly_work_limit ?? 200, 'used_hours' => 0.00]
            );

            if (($driverSchedule->used_hours + $estHours) > $driverSchedule->total_hours) {
                DB::rollBack();
                return redirect()->back()->withInput()->with('error', 'Driver tidak memiliki cukup jam kerja bulan ini.');
            }

            if ($guide) {
                $guideSchedule = WorkSchedule::firstOrCreate(
                    ['user_id' => $guide->id, 'month' => $month, 'year' => $year],
                    ['total_hours' => $guide->monthly_work_limit ?? 200, 'used_hours' => 0.00]
                );

                if (($guideSchedule->used_hours + $estHours) > $guideSchedule->total_hours) {
                    DB::rollBack();
                    return redirect()->back()->withInput()->with('error', 'Guide tidak memiliki cukup jam kerja bulan ini.');
                }
            }

            $assignment = Assignment::create([
                'order_id'     => $order->id,
                'driver_id'    => $driver->id,
                'guide_id'     => $guide?->id,
                'assigned_by'  => Auth::id(),
                'status'       => 'pending',
                'assigned_at'  => now(),
                'note'         => $data['note'] ?? null,
            ]);

            $order->update(['status' => 'assigned']);

            DB::commit();

            // event(new NewAssignmentCreated($assignment));

            return redirect()->route('assignments.index')->with('success', 'Assignment berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Assignment.store error: ' . $e->getMessage(), ['payload' => $data, 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat membuat assignment.');
        }
    }

    /**
     * Ubah status assignment oleh driver/guide
     */
    public function changeStatus(Request $request, Assignment $assignment)
    {
        $request->validate([
            'status' => 'required|in:accepted,completed,declined',
        ]);

        $user = Auth::user();
        $status = $request->status;

        if (!in_array($user->role, ['driver', 'guide']) ||
            ($assignment->driver_id !== $user->id && $assignment->guide_id !== $user->id)) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah status tugas ini.');
        }

        // Accepted → catat waktu mulai
        if ($status === 'accepted' && !$assignment->workstart) {
            $assignment->workstart = now();
        }

        // Completed → hitung durasi dan update jam kerja
        if ($status === 'completed' && !$assignment->workend) {
            $assignment->workend = now();

            if ($assignment->workstart) {
                $start = Carbon::parse($assignment->workstart);
                $end = Carbon::parse($assignment->workend);
                $diffMinutes = max(1, $end->diffInMinutes($start));

                $month = now()->month;
                $year = now()->year;
                $userId = $assignment->driver_id ?? $assignment->guide_id;

                $ws = WorkSchedule::firstOrCreate(
                    ['user_id' => $userId, 'month' => $month, 'year' => $year],
                    ['total_hours' => 200, 'used_hours' => 0.00]
                );

                $newUsed = $this->addUsedHoursHMM($ws->used_hours, $diffMinutes);

                $ws->update(['used_hours' => $newUsed]);
            }
        }

        $assignment->status = $status;
        $assignment->save();

        return back()->with('success', 'Status assignment diperbarui.');
    }

    /**
     * Helper: tambahkan menit ke format jam H.MM (mis: 2.30, 0.45, 3.00)
     */
    private function addUsedHoursHMM($currentUsedHours, int $minutesToAdd): float
    {
        $current = sprintf('%.2f', (float)($currentUsedHours ?? 0));
        [$h, $m] = array_pad(explode('.', $current), 2, 0);
        $h = (int)$h;
        $m = (int)$m;

        $total = $h * 60 + $m + $minutesToAdd;

        $newHours = intdiv($total, 60);
        $newMinutes = $total % 60;

        return (float) sprintf('%d.%02d', $newHours, $newMinutes);
    }

    /**
     * Tampilkan detail assignment
     */
    // public function show(Assignment $assignment)
    // {
    //     try {
    //         $assignment->load(['order.product', 'driver', 'guide', 'assignedBy']);
    //         return view('assignments.show', compact('assignment'));
    //     } catch (\Throwable $e) {
    //         Log::error('Assignment.show error: ' . $e->getMessage(), ['assignment_id' => $assignment->id]);
    //         return redirect()->back()->with('error', 'Gagal membuka detail assignment.');
    //     }
    // }

    /**
     * Hapus assignment
     */
    public function destroy(Assignment $assignment)
    {
        try {
            $assignment->delete();
            return redirect()->route('assignments.index')->with('success', 'Assignment dihapus.');
        } catch (\Throwable $e) {
            Log::error('Assignment.destroy error: ' . $e->getMessage(), ['assignment_id' => $assignment->id]);
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
                if ($user->role === 'driver') $q->where('driver_id', $user->id);
                if ($user->role === 'guide') $q->where('guide_id', $user->id);
            })->with(['order.product'])->orderBy('assigned_at', 'desc')->get();

            return view('assignments.my', compact('assignments'));
        } catch (\Throwable $e) {
            Log::error('Assignment.myAssignments error: ' . $e->getMessage(), ['user_id' => Auth::id()]);
            return redirect()->back()->with('error', 'Gagal mengambil daftar tugas Anda.');
        }
    }

    /**
     * Hitung estimasi jam dari order
     */
    protected function calculateEstimatedHours(Order $order): int
    {
        $minutes = (int)($order->estimated_duration_minutes ?? 0);

        if ($minutes <= 0 && $order->pickup_time && $order->arrival_time) {
            try {
                $start = strtotime($order->pickup_time);
                $end = strtotime($order->arrival_time);
                $diff = max(0, $end - $start);
                $minutes = (int) round($diff / 60);
            } catch (\Throwable $e) {
                Log::warning('calculateEstimatedHours parse error: ' . $e->getMessage());
                $minutes = 60;
            }
        }

        if ($minutes <= 0) $minutes = 60;
        return (int) ceil($minutes / 60);
    }
}
