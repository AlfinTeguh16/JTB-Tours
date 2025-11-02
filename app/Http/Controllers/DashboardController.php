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
    /**
     * Show dashboard with role-aware stats and charts.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $now = Carbon::now();

            // month/year filter (optional via query)
            $year = (int) $request->query('year', $now->year);
            $month = (int) $request->query('month', $now->month);

            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth()->toDateTimeString();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->toDateTimeString();

            // Safe helpers: check models/tables exist before using
            $ordersTableExists = Schema::hasTable('orders');
            $usersTableExists = Schema::hasTable('users');
            $productsTableExists = Schema::hasTable('products');
            $workSchedulesTableExists = Schema::hasTable('work_schedules');

            // Basic counts (defaults 0)
            $ordersThisMonth = 0;
            $assignedThisMonth = 0;
            $completedThisMonth = 0;
            $activeDrivers = 0;

            if ($ordersTableExists) {
                $ordersThisMonth = (int) DB::table('orders')
                    ->whereBetween('pickup_time', [$startOfMonth, $endOfMonth])
                    ->count();

                $assignedThisMonth = (int) DB::table('orders')
                    ->whereBetween('pickup_time', [$startOfMonth, $endOfMonth])
                    ->where('status', 'assigned')
                    ->count();

                $completedThisMonth = (int) DB::table('orders')
                    ->whereBetween('pickup_time', [$startOfMonth, $endOfMonth])
                    ->where('status', 'completed')
                    ->count();
            }

            if ($usersTableExists) {
                $activeDrivers = (int) DB::table('users')
                    ->whereIn('role', ['driver', 'guide'])
                    ->where('status', 'online')
                    ->count();
            }

            // monthly orders for last 12 months for chart
            $monthlyOrders = [];
            for ($i = 11; $i >= 0; $i--) {
                $dt = $now->copy()->subMonths($i);
                $start = $dt->copy()->startOfMonth()->toDateTimeString();
                $end = $dt->copy()->endOfMonth()->toDateTimeString();

                $count = 0;
                if ($ordersTableExists) {
                    $count = (int) DB::table('orders')->whereBetween('pickup_time', [$start, $end])->count();
                }

                $monthlyOrders[] = [
                    'label' => $dt->format('M Y'),
                    'count' => $count,
                    'year' => $dt->year,
                    'month' => $dt->month,
                ];
            }

            // product distribution (pie) for the selected month
            $productDistribution = [];
            if ($ordersTableExists) {
                $rows = DB::table('orders')
                    ->select('product_id', DB::raw('count(*) as total'))
                    ->whereBetween('pickup_time', [$startOfMonth, $endOfMonth])
                    ->groupBy('product_id')
                    ->get();

                foreach ($rows as $r) {
                    $label = $r->product_id ? ("Product #{$r->product_id}") : 'Unknown';
                    // try to fetch product name if possible
                    if ($productsTableExists && $r->product_id) {
                        $prod = DB::table('products')->where('id', $r->product_id)->first();
                        if ($prod && isset($prod->name)) {
                            $label = $prod->name;
                        }
                    }
                    $productDistribution[] = [
                        'product_id' => $r->product_id,
                        'label' => $label,
                        'count' => (int) $r->total,
                    ];
                }
            }

            // If user is driver/guide: personal stats (assignment count & work schedule)
            $personal = [
                'assignments_count' => null,
                'work_schedule' => null,
            ];

            if ($user && Schema::hasTable('assignments')) {
                $personal['assignments_count'] = (int) DB::table('assignments')
                    ->where('user_id', $user->id)
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
                } else {
                    if ($usersTableExists) {
                        $u = DB::table('users')->where('id', $user->id)->first();
                        if ($u) {
                            $personal['work_schedule'] = [
                                'total_hours' => $u->monthly_work_limit ?? 0,
                                'used_hours' => $u->used_hours ?? 0,
                            ];
                        }
                    }
                }
            }

            // top drivers by used_hours for the month (if work_schedules exists)
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

            // package up data for view
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
            ];

            return view('dashboard', $data);
        } catch (\Throwable $e) {
            Log::error('DashboardController@index error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return redirect()->back()->with('error', 'Gagal memuat dashboard.');
        }
    }
}
