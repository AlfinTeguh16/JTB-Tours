<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Assignment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\OrdersExport;
use App\Exports\OrdersQueryExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->query('year', date('Y'));
        $month = $request->query('month', null);

        $ordersQuery = Order::select(DB::raw("MONTH(pickup_time) as month"), DB::raw("COUNT(*) as total"))
            ->whereYear('pickup_time', $year)
            ->groupBy(DB::raw("MONTH(pickup_time)"))
            ->orderBy('month')
            ->get();

        $ordersPerMonth = [];
        for ($m=1;$m<=12;$m++) {
            $row = $ordersQuery->firstWhere('month',$m);
            $ordersPerMonth[$m] = $row ? (int)$row->total : 0;
        }

        $assignAccepted = Assignment::select(DB::raw("MONTH(assigned_at) as month"), DB::raw("COUNT(*) as total"))
            ->where('status','accepted')
            ->whereYear('assigned_at',$year)
            ->groupBy(DB::raw("MONTH(assigned_at)"))
            ->get();

        $acceptedPerMonth = [];
        for ($m=1;$m<=12;$m++) {
            $row = $assignAccepted->firstWhere('month',$m);
            $acceptedPerMonth[$m] = $row ? (int)$row->total : 0;
        }

        $productUsage = Product::select('products.id','products.name', DB::raw('COUNT(orders.id) as total'))
            ->leftJoin('orders','products.id','=','orders.product_id')
            ->leftJoin('assignments','orders.id','=','assignments.order_id')
            ->where('assignments.status','accepted')
            ->whereYear('assignments.assigned_at',$year)
            ->groupBy('products.id','products.name')
            ->get();

        $assignmentsByStatusQuery = Assignment::select('status', DB::raw('count(*) as total'))
            ->when($year, fn($q) => $q->whereYear('assigned_at', $year))
            ->when($month, fn($q) => $month ? $q->whereMonth('assigned_at', $month) : $q)
            ->groupBy('status')
            ->get()
            ->pluck('total','status')
            ->toArray();

        return view('reports.index', [
            'year'=>$year,
            'month'=>$month,
            'ordersPerMonth'=>$ordersPerMonth,
            'acceptedPerMonth'=>$acceptedPerMonth,
            'productUsage'=>$productUsage,
            'assignmentsByStatus'=>$assignmentsByStatusQuery
        ]);
    }

    // Excel export (uses chunked export for safety)
    public function exportExcel(Request $request)
    {
        $this->authorizeExport(); // helper check (defined below) - optional

        $year = $request->query('year', date('Y'));
        $month = $request->query('month', null);

        $fileName = "orders_{$year}" . ($month ? "_{$month}" : "") . ".xlsx";

        // Use chunked query-based export to handle large datasets
        return Excel::download(new OrdersQueryExport($year, $month), $fileName);
    }

    // PDF export
    public function exportPdf(Request $request)
    {
        $this->authorizeExport();

        $year = $request->query('year', date('Y'));
        $month = $request->query('month', null);

        $orders = Order::with('product')
            ->when($year, fn($q) => $q->whereYear('pickup_time',$year))
            ->when($month, fn($q) => $q->whereMonth('pickup_time',$month))
            ->orderBy('pickup_time','desc')
            ->get();

        $pdf = Pdf::loadView('reports.pdf_orders', compact('orders','year','month'))->setPaper('a4','portrait');

        $fileName = "orders_report_{$year}" . ($month ? "_{$month}" : "") . ".pdf";
        return $pdf->download($fileName);
    }

    // optional simple role guard - adjust to your app roles
    protected function authorizeExport()
    {
        // if you want, check current user role here and abort(403) if not allowed
        // e.g. if (!auth()->user() || !in_array(auth()->user()->role, ['super_admin','admin'])) abort(403);
        return true;
    }
}
