<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Assignment;
use App\Models\WorkSchedule;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user->role ?? 'admin';

        if (in_array($role, ['admin', 'super_admin', 'staff'])) {
            return $this->adminIndex($request);
        } elseif (in_array($role, ['driver', 'guide'])) {
            return $this->driverGuideIndex($request);
        } else {
            return back()->with('error', 'Role tidak dikenali.');
        }
    }

    // ===== ADMIN DASHBOARD =====
    private function adminIndex(Request $request)
    {
        try {
            $now = Carbon::now();
            $year = (int) $request->query('year', $now->year);
            $month = (int) $request->query('month', $now->month);

            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth   = Carbon::create($year, $month, 1)->endOfMonth();

            // Ambil data satu per satu
            $ordersThisMonth      = $this->getOrdersCountThisMonth($startOfMonth, $endOfMonth);
            $assignedThisMonth    = $this->getAssignedCountThisMonth($startOfMonth, $endOfMonth);
            $completedThisMonth   = $this->getCompletedCountThisMonth($startOfMonth, $endOfMonth);
            $activeDrivers        = $this->getActiveDriversCount();
            $monthlyOrders        = $this->getMonthlyOrdersLast12Months($now);
            $productDistribution  = $this->getProductDistributionThisMonth($startOfMonth, $endOfMonth);
            $topDrivers           = $this->getTopDriversThisMonth($year, $month);
            $availableYears       = $this->getAvailableYears();

            return view('dashboard.admin', compact(
                'ordersThisMonth',
                'assignedThisMonth',
                'completedThisMonth',
                'activeDrivers',
                'monthlyOrders',
                'productDistribution',
                'topDrivers',
                'month',
                'year',
                'availableYears'
            ));
        } catch (\Throwable $e) {
            Log::error('Admin dashboard error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Gagal memuat dashboard admin.');
        }
    }

    // ─── Helper Functions (Admin) ──────────────────────────────────────────────

    private function getOrdersCountThisMonth(Carbon $start, Carbon $end): int
    {
        if (!Schema::hasTable('orders')) return 0;

        return DB::table('orders')
            ->whereBetween('pickup_time', [$start, $end])
            ->count();
    }

    private function getAssignedCountThisMonth(Carbon $start, Carbon $end): int
    {
        if (!Schema::hasTable('assignments')) return 0;

        return DB::table('assignments')
            ->whereBetween('assigned_at', [$start, $end])
            ->where('status', 'accepted')
            ->count();
    }

    private function getCompletedCountThisMonth(Carbon $start, Carbon $end): int
    {
        if (!Schema::hasTable('assignments')) return 0;

        return DB::table('assignments')
            ->whereBetween('assigned_at', [$start, $end])
            ->where('status', 'completed')
            ->count();
    }

    private function getActiveDriversCount(): int
    {
        if (!Schema::hasTable('users')) return 0;

        return DB::table('users')
            ->whereIn('role', ['driver', 'guide'])
            ->where('status', 'online')
            ->count();
    }

    private function getMonthlyOrdersLast12Months(Carbon $now): array
    {
        $ordersExist = Schema::hasTable('orders');
        $assignmentsExist = Schema::hasTable('assignments');

        $monthlyData = [];

        for ($i = 11; $i >= 0; $i--) {
            $dt = $now->copy()->subMonths($i);
            $start = $dt->copy()->startOfMonth()->toDateTimeString();
            $end = $dt->copy()->endOfMonth()->toDateTimeString();

            $orderCount = $ordersExist
                ? DB::table('orders')
                    ->whereBetween('pickup_time', [$start, $end])
                    ->count()
                : 0;

            $completedCount = $assignmentsExist
                ? DB::table('assignments')
                    ->whereBetween('assigned_at', [$start, $end])
                    ->where('status', 'completed')
                    ->count()
                : 0;

            $monthlyData[] = [
                'label'     => $dt->format('M Y'),
                'year'      => $dt->year,
                'month'     => $dt->month,
                'orders'    => (int) $orderCount,
                'completed' => (int) $completedCount,
            ];
        }

        return $monthlyData;
    }

    private function getProductDistributionThisMonth(Carbon $start, Carbon $end): array
    {
        if (!Schema::hasTable('orders')) return [];

        $rows = DB::table('orders')
            ->select('product_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('pickup_time', [$start, $end])
            ->groupBy('product_id')
            ->get();

        $distribution = [];
        $productsExist = Schema::hasTable('products');

        foreach ($rows as $r) {
            $label = 'Unknown Product';
            if ($productsExist && $r->product_id) {
                $prod = DB::table('products')->where('id', $r->product_id)->first();
                $label = $prod?->name ?? $label;
            }

            $distribution[] = [
                'product_id' => $r->product_id,
                'label'      => $label,
                'count'      => (int) $r->total,
            ];
        }

        return $distribution;
    }

    private function getTopDriversThisMonth(int $year, int $month): array
    {
        if (!Schema::hasTable('work_schedules')) return [];

        $rows = DB::table('work_schedules')
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('used_hours')
            ->limit(6)
            ->get();

        $topDrivers = [];
        $usersExist = Schema::hasTable('users');

        foreach ($rows as $r) {
            $name = 'User #' . $r->user_id;
            if ($usersExist) {
                $user = DB::table('users')->where('id', $r->user_id)->first();
                $name = $user?->name ?? $name;
            }

            $topDrivers[] = [
                'user_id'     => $r->user_id,
                'name'        => $name,
                'used_hours'  => (int) ($r->used_hours ?? 0),
                'total_hours' => (int) ($r->total_hours ?? 0),
            ];
        }

        return $topDrivers;
    }

    private function getAvailableYears(): array
    {
        if (!Schema::hasTable('orders')) {
            return [Carbon::now()->year];
        }

        return DB::table('orders')
            ->select(DB::raw('DISTINCT YEAR(pickup_time) as year'))
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn($y) => (int) $y)
            ->toArray();
    }

    // ===== DRIVER / GUIDE DASHBOARD =====
    private function driverGuideIndex(Request $request)
    {
        $user = Auth::user();

        try {
            $now = Carbon::now();

            // Tahun untuk chart + filter
            $year = (int) $request->query('year', $now->year);

            // Bulan untuk card (opsional, kalau mau nanti bisa tambahkan filter bulan)
            $month = (int) $request->query('month', $now->month);

            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth   = Carbon::create($year, $month, 1)->endOfMonth();

            // Query dasar assignment milik user
            $baseQuery = Assignment::where(function ($q) use ($user) {
                $q->where('driver_id', $user->id)
                  ->orWhere('guide_id', $user->id);
            });

            // Total tugas bulan ini
            $assignmentsCount = (clone $baseQuery)
                ->whereBetween('assigned_at', [$startOfMonth, $endOfMonth])
                ->count();

            // Assignment completed bulan ini untuk hitung jam
            $completedAssignments = (clone $baseQuery)
                ->whereBetween('assigned_at', [$startOfMonth, $endOfMonth])
                ->where('status', 'completed')
                ->get();

            $usedMinutes = 0;
            foreach ($completedAssignments as $a) {
                if ($a->workstart && $a->workend) {
                    $usedMinutes += Carbon::parse($a->workstart)
                        ->diffInMinutes(Carbon::parse($a->workend));
                }
            }

            $usedHours = round($usedMinutes / 60, 1);

            // Ambil work schedule untuk batas jam (kalau ada)
            $workSchedule = WorkSchedule::where('user_id', $user->id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            $totalHours = (int) ($workSchedule?->total_hours ?? $user->monthly_work_limit ?? 200);

            $usagePercent = $totalHours > 0
                ? min(100, round(($usedHours / $totalHours) * 100))
                : 0;

            // 5 tugas terbaru bulan ini
            $recentAssignments = (clone $baseQuery)
                ->with(['order.product'])
                ->whereBetween('assigned_at', [$startOfMonth, $endOfMonth])
                ->orderByDesc('assigned_at')
                ->limit(5)
                ->get();

            // Total tugas completed bulan ini (untuk card)
            $completedThisMonth = $completedAssignments->count();

            // ===== CHART: Completed assignments per month (1 tahun) =====
            $completedPerMonthRaw = (clone $baseQuery)
                ->select(DB::raw('MONTH(assigned_at) as month'), DB::raw('COUNT(*) as total'))
                ->whereYear('assigned_at', $year)
                ->where('status', 'completed')
                ->groupBy(DB::raw('MONTH(assigned_at)'))
                ->orderBy(DB::raw('MONTH(assigned_at)'))
                ->get();

            $completedPerMonth = [];
            for ($m = 1; $m <= 12; $m++) {
                $row = $completedPerMonthRaw->firstWhere('month', $m);
                $completedPerMonth[] = [
                    'month'     => $m,
                    'label'     => Carbon::create($year, $m, 1)->format('M'),
                    'completed' => $row ? (int) $row->total : 0,
                ];
            }

            // Tahun-tahun yang tersedia untuk filter (berdasarkan assignments user ini)
            if (!Schema::hasTable('assignments')) {
                $availableYears = [$now->year];
            } else {
                $availableYears = DB::table('assignments')
                    ->where(function ($q) use ($user) {
                        $q->where('driver_id', $user->id)
                          ->orWhere('guide_id', $user->id);
                    })
                    ->select(DB::raw('DISTINCT YEAR(assigned_at) as year'))
                    ->orderByDesc('year')
                    ->pluck('year')
                    ->map(fn($y) => (int) $y)
                    ->toArray();

                if (empty($availableYears)) {
                    $availableYears = [$now->year];
                }
            }

            return view('dashboard.driver-guide', compact(
                'assignmentsCount',
                'usedHours',
                'totalHours',
                'usagePercent',
                'recentAssignments',
                'completedThisMonth',
                'month',
                'year',
                'completedPerMonth',
                'availableYears'
            ));

        } catch (\Throwable $e) {
            Log::error('Driver/Guide dashboard error: ' . $e->getMessage(), [
                'user_id' => $user?->id,
                'trace'   => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Gagal memuat dashboard. Silakan coba lagi.');
        }
    }
}
