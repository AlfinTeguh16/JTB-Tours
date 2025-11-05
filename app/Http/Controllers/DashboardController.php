<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $now = Carbon::now();

            // Filter tahun & bulan dari query
            $year = (int) $request->query('year', $now->year);
            $month = (int) $request->query('month', $now->month);

            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth()->toDateTimeString();
            $endOfMonth   = Carbon::create($year, $month, 1)->endOfMonth()->toDateTimeString();

            // Cek tabel yang tersedia
            $ordersTableExists        = Schema::hasTable('orders');
            $assignmentsTableExists   = Schema::hasTable('assignments');
            $usersTableExists         = Schema::hasTable('users');
            $productsTableExists      = Schema::hasTable('products');
            $workSchedulesTableExists = Schema::hasTable('work_schedules');

            // Default stats
            $ordersThisMonth = 0;
            $assignedThisMonth = 0;
            $completedThisMonth = 0;
            $activeDrivers = 0;

            // Hitung data order bulan ini
            if ($ordersTableExists) {
                $ordersThisMonth = (int) DB::table('orders')
                    ->whereBetween('pickup_time', [$startOfMonth, $endOfMonth])
                    ->count();
            }

            // Assignment bulan ini
            if ($assignmentsTableExists) {
                $assignedThisMonth = (int) DB::table('assignments')
                    ->whereBetween('assigned_at', [$startOfMonth, $endOfMonth])
                    ->where('status', 'accepted')
                    ->count();

                $completedThisMonth = (int) DB::table('assignments')
                    ->whereBetween('assigned_at', [$startOfMonth, $endOfMonth])
                    ->where('status', 'completed')
                    ->count();
            }

            // Driver/guide aktif
            if ($usersTableExists) {
                $activeDrivers = (int) DB::table('users')
                    ->whereIn('role', ['driver', 'guide'])
                    ->where('status', 'online')
                    ->count();
            }

            // Data untuk chart order 12 bulan terakhir
            $monthlyOrders = [];
            if ($ordersTableExists) {
                for ($i = 11; $i >= 0; $i--) {
                    $dt = $now->copy()->subMonths($i);
                    $start = $dt->copy()->startOfMonth()->toDateTimeString();
                    $end = $dt->copy()->endOfMonth()->toDateTimeString();

                    $count = (int) DB::table('orders')
                        ->whereBetween('pickup_time', [$start, $end])
                        ->count();

                    $monthlyOrders[] = [
                        'label' => $dt->format('M Y'),
                        'count' => $count,
                        'year' => $dt->year,
                        'month' => $dt->month,
                    ];
                }
            }

            // Distribusi produk (pie chart)
            $productDistribution = [];
            if ($ordersTableExists) {
                $rows = DB::table('orders')
                    ->select('product_id', DB::raw('COUNT(*) as total'))
                    ->whereBetween('pickup_time', [$startOfMonth, $endOfMonth])
                    ->groupBy('product_id')
                    ->get();

                foreach ($rows as $r) {
                    $label = 'Unknown Product';
                    if ($productsTableExists && $r->product_id) {
                        $prod = DB::table('products')->where('id', $r->product_id)->first();
                        if ($prod && isset($prod->name)) $label = $prod->name;
                    }
                    $productDistribution[] = [
                        'product_id' => $r->product_id,
                        'label' => $label,
                        'count' => (int) $r->total,
                    ];
                }
            }

            // Statistik pribadi driver/guide
            $personal = ['assignments_count' => null, 'work_schedule' => null];
            if ($assignmentsTableExists && $user) {
                $personal['assignments_count'] = DB::table('assignments')
                    ->where(function ($q) use ($user) {
                        $q->where('driver_id', $user->id)
                          ->orWhere('guide_id', $user->id);
                    })
                    ->count();
            }

            if ($workSchedulesTableExists && $user) {
                $ws = DB::table('work_schedules')
                    ->where('user_id', $user->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->first();

                if ($ws) {
                    $personal['work_schedule'] = [
                        'total_hours' => $ws->total_hours ?? 0,
                        'used_hours' => $ws->used_hours ?? 0,
                    ];
                }
            }

            // Top driver / guide berdasarkan jam kerja
            $topDrivers = [];
            if ($workSchedulesTableExists) {
                $rows = DB::table('work_schedules')
                    ->where('month', $month)
                    ->where('year', $year)
                    ->orderByDesc('used_hours')
                    ->limit(6)
                    ->get();

                foreach ($rows as $r) {
                    $name = 'User #' . $r->user_id;
                    if ($usersTableExists) {
                        $u = DB::table('users')->where('id', $r->user_id)->first();
                        if ($u && isset($u->name)) $name = $u->name;
                    }
                    $topDrivers[] = [
                        'user_id' => $r->user_id,
                        'name' => $name,
                        'used_hours' => $r->used_hours ?? 0,
                        'total_hours' => $r->total_hours ?? 0,
                    ];
                }
            }

            // Tahun tersedia untuk dropdown filter
            $availableYears = [];
            if ($ordersTableExists) {
                $availableYears = DB::table('orders')
                    ->select(DB::raw('DISTINCT YEAR(pickup_time) as year'))
                    ->orderByDesc('year')
                    ->pluck('year')
                    ->toArray();
            }

            // Siapkan data ke view
            $data = [
                'ordersThisMonth' => $ordersThisMonth,
                'assignedThisMonth' => $assignedThisMonth,
                'completedThisMonth' => $completedThisMonth,
                'activeDrivers' => $activeDrivers,
                'monthlyOrders' => $monthlyOrders,
                'productDistribution' => $productDistribution,
                'personal' => $personal,
                'topDrivers' => $topDrivers,
                'month' => $month,
                'year' => $year,
                'availableYears' => $availableYears,
            ];

            // Pilih view sesuai role user
            $role = $user->role ?? 'admin';
            $roleViewMap = [
                'super_admin' => 'dashboard.super-admin',
                'admin'       => 'dashboard.admin',
                'staff'       => 'dashboard.admin',
                'driver'      => 'dashboard.driver-guide',
                'guide'       => 'dashboard.driver-guide',
            ];

            $view = $roleViewMap[$role] ?? 'dashboard.admin';

            return view($view, $data);
            // return response()->json($data);

        } catch (\Throwable $e) {
            Log::error('DashboardController@index error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Gagal memuat dashboard.');
        }
    }
}
