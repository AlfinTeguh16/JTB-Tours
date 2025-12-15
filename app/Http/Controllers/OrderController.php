<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of the orders with optional filters.
     */
    public function index(Request $request)
    {
        try {
            $q = Order::with('product', 'createdBy')->orderBy('pickup_time', 'desc');

            if ($request->filled('q')) {
                $search = $request->q;
                $q->where(function ($w) use ($search) {
                    $w->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('pickup_location', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%");
                });
            }

            if ($request->filled('product_id')) {
                $q->where('product_id', $request->product_id);
            }

            if ($request->filled('status')) {
                $q->where('status', $request->status);
            } else {
                // default: sembunyikan yang sudah completed
                $q->where('status', '!=', 'completed');
            }

            if ($request->filled('from')) {
                $q->whereDate('pickup_time', '>=', $request->from);
            }

            if ($request->filled('to')) {
                $q->whereDate('pickup_time', '<=', $request->to);
            }

            $orders = $q->paginate(25)->withQueryString();

            $products = Product::orderBy('name')->get();

            // Format tambahan untuk UI
            $orders->getCollection()->transform(function ($o) {
                try {
                    $o->formatted_pickup = $o->pickup_time
                        ? \Carbon\Carbon::parse($o->pickup_time)->format('d M Y H:i')
                        : '-';
                } catch (\Throwable $e) {
                    $o->formatted_pickup = '-';
                }

                try {
                    $o->formatted_arrival = $o->arrival_time
                        ? \Carbon\Carbon::parse($o->arrival_time)->format('d M Y H:i')
                        : '-';
                } catch (\Throwable $e) {
                    $o->formatted_arrival = '-';
                }

                $o->summary_people =
                    ($o->adults ?? 0) . ' adults · ' .
                    ($o->children ?? 0) . ' children · ' .
                    ($o->babies ?? 0) . ' babies';

                $o->summary_contact = ($o->email ?? '-') . ' · ' . ($o->phone ?? '-');

                return $o;
            });

            // 🔔 dipakai JS notifikasi sebagai titik awal
            $lastOrderId = Order::max('id') ?? 0;

            return view('orders.index', compact('orders', 'products', 'lastOrderId'));
        } catch (\Throwable $e) {
            Log::error('Order.index error: ' . $e->getMessage(), [
                'trace'   => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return redirect()->back()->with('error', 'Gagal mengambil daftar order.');
        }
    }

    /**
     * Show the form for creating a new order.
     */
    public function create()
    {
        try {
            $products = Product::with('branches')->orderBy('name')->get();
            // Get unique vehicle types from DB, combined with defaults
            $dbTypes = \App\Models\Vehicle::select('type')->distinct()->pluck('type')->toArray();
            $defaultTypes = ['Avanza', 'Innova', 'HiAce', 'Bus', 'Alphard', 'APV']; // Common defaults
            $vehicleTypes = array_unique(array_merge($dbTypes, $defaultTypes));
            sort($vehicleTypes);

            return view('orders.create', compact('products', 'vehicleTypes'));
        } catch (\Throwable $e) {
            Log::error('Order.create error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal membuka form pembuatan order.');
        }
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name'              => 'required|string|max:255',
            'email'                      => 'nullable|email',
            'phone'                      => 'nullable|string|max:25',
            'pickup_time'                => 'required|date',
            'arrival_time'               => 'nullable|date|after_or_equal:pickup_time',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'passengers'                 => 'required|integer|min:1',
            'pickup_location'            => 'nullable|string|max:255',
            'destination'                => 'nullable|string|max:255',
            'product_id'                 => 'required|exists:products,id',
            'product_branch_id'          => 'nullable|exists:product_branches,id',
            'adults'                     => 'nullable|integer|min:0',
            'children'                   => 'nullable|integer|min:0',
            'babies'                     => 'nullable|integer|min:0',
            'vehicle_count'              => 'nullable|integer|min:1',
            'note'                       => 'nullable|string|max:2000',
            'vehicle_ids'                => 'nullable|array',
            'vehicle_ids.*'              => 'exists:vehicles,id',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::with('branches')->find($validated['product_id']);

            $adults  = $validated['adults']   ?? 0;
            $children = $validated['children'] ?? 0;
            $babies  = $validated['babies']   ?? 0;
            $totalPassengers = $adults + $children + $babies;

            // Handle Vehicle Selection
            $vehicleIds = $validated['vehicle_ids'] ?? [];
            if (!empty($vehicleIds)) {
                // Determine vehicle count from selection
                $validated['vehicle_count'] = count($vehicleIds);
            } else {
                // Auto-calculate based on capacity if no specific vehicles selected
                // We ignore frontend vehicle_count because the manual input is hidden/removed in UI.
                $vehicleCap = $product->capacity ?? 4;
                $validated['vehicle_count'] = ceil($totalPassengers / max(1, $vehicleCap));
            }

            // Duration Logic
            // Priority: User Input > Branch Duration > Product Default > 60 min
            if (empty($validated['estimated_duration_minutes'])) {
                if (!empty($validated['product_branch_id'])) {
                    // Get duration from selected branch
                    $branch = $product->branches->find($validated['product_branch_id']);
                    if ($branch) {
                        $validated['estimated_duration_minutes'] = $branch->duration_minutes;
                    }
                } 
                
                // Fallback to time diff if still empty and checks out
                if (empty($validated['estimated_duration_minutes']) && !empty($validated['arrival_time']) && !empty($validated['pickup_time'])) {
                    $diffMinutes = max(1, (int) round(
                        (strtotime($validated['arrival_time']) - strtotime($validated['pickup_time'])) / 60
                    ));
                    $validated['estimated_duration_minutes'] = $diffMinutes;
                }
                
                // Final fallback
                if (empty($validated['estimated_duration_minutes'])) {
                   $validated['estimated_duration_minutes'] = (int)($product->hour * 60) ?: 60;
                }
            }

            $validated['created_by'] = Auth::check() ? Auth::id() : null;
            $validated['status']     = 'pending';

            $order = Order::create($validated);

            // Attach Vehicles
            if (!empty($vehicleIds)) {
                $order->vehicles()->attach($vehicleIds);
                // Also could update status to 'assigned' if that's the logic intended, 
                // but usually assignment means Driver + Guide too. 
                // For now, keep as pending until Assignment is created.
            }

            DB::commit();

            // 🔔 Kirim Notifikasi ke Staff jika pembuat adalah Admin
            $user = auth()->user();
            if ($user && ($user->role === 'admin' || $user->role === 'super_admin')) {
                 $staffUsers = \App\Models\User::where('role', 'staff')->get();
                 if ($staffUsers->isNotEmpty()) {
                     \Illuminate\Support\Facades\Notification::send($staffUsers, new \App\Notifications\NewOrderNotification($order, $user));
                 }
            }

        return redirect()->route('orders.index')->with('success', 'Order created successfully.');
    } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order.store error: ' . $e->getMessage(), [
                'payload' => $validated,
                'trace'   => $e->getTraceAsString()
            ]);
            return redirect()->back()->withInput()->with('error', 'Gagal membuat order.');
        }
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        try {
            $order->load(['product', 'assignments']);
            return view('orders.show', compact('order'));
        } catch (\Throwable $e) {
            Log::error('Order.show error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace'    => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Gagal membuka detail order.');
        }
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order)
    {
        try {
            $products = Product::with('branches')->orderBy('name')->get();
             // Get unique vehicle types from DB, combined with defaults
            $dbTypes = \App\Models\Vehicle::select('type')->distinct()->pluck('type')->toArray();
            $defaultTypes = ['Avanza', 'Innova', 'HiAce', 'Bus', 'Alphard', 'APV']; // Common defaults
            $vehicleTypes = array_unique(array_merge($dbTypes, $defaultTypes));
            sort($vehicleTypes);

            return view('orders.edit', compact('order', 'products', 'vehicleTypes'));
        } catch (\Throwable $e) {
            Log::error('Order.edit error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace'    => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Gagal membuka form edit order.');
        }
    }

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_name'              => 'required|string|max:255',
            'email'                      => 'nullable|email',
            'phone'                      => 'nullable|string|max:25',
            'pickup_time'                => 'required|date',
            'arrival_time'               => 'nullable|date|after_or_equal:pickup_time',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'passengers'                 => 'required|integer|min:1',
            'pickup_location'            => 'nullable|string|max:255',
            'destination'                => 'nullable|string|max:255',
            'product_id'                 => 'required|exists:products,id',
            'product_branch_id'          => 'nullable|exists:product_branches,id', // Added
            'adults'                     => 'nullable|integer|min:0',
            'children'                   => 'nullable|integer|min:0',
            'babies'                     => 'nullable|integer|min:0',
            'vehicle_count'              => 'nullable|integer|min:1',
            'note'                       => 'nullable|string|max:2000',
        ]);

        DB::beginTransaction();
        try {
            // server-side capacity check
            $product = Product::find($validated['product_id']);
            $adults  = $validated['adults']   ?? 0;
            $children = $validated['children'] ?? 0;
            $babies  = $validated['babies']   ?? 0;
            $totalPassengers = $adults + $children + $babies;

            if ($product && $product->capacity !== null && $product->capacity > 0 && $totalPassengers > $product->capacity) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'Total penumpang melebihi kapasitas product (' . $product->capacity . ').'
                );
            }

            // recalc estimated duration if not provided but arrival_time exists
            $est = $validated['estimated_duration_minutes'] ?? null;
            if (empty($est) && !empty($validated['arrival_time']) && !empty($validated['pickup_time'])) {
                $diffMinutes = max(1, (int) round(
                    (strtotime($validated['arrival_time']) - strtotime($validated['pickup_time'])) / 60
                ));
                $validated['estimated_duration_minutes'] = $diffMinutes;
            } elseif (empty($est) && empty($order->estimated_duration_minutes)) {
                $validated['estimated_duration_minutes'] = 60;
            }

            $order->update($validated);

            DB::commit();

            return redirect()->route('orders.index')->with('success', 'Order diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order.update error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'payload'  => $validated,
                'trace'    => $e->getTraceAsString()
            ]);
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui order.');
        }
    }

    /**
     * Remove the specified order from storage.
     * Prevent deletion if order is assigned/completed to avoid breaking assignments.
     */
    public function destroy(Order $order)
    {
        DB::beginTransaction();
        try {
            // prevent deletion of assigned/completed orders
            if (in_array($order->status, ['assigned', 'completed'])) {
                return redirect()->back()->with(
                    'error',
                    'Order yang sudah di-assign atau completed tidak dapat dihapus.'
                );
            }

            $order->delete();

            DB::commit();

            return redirect()->route('orders.index')->with('success', 'Order dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order.destroy error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace'    => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Gagal menghapus order.');
        }
    }

    /**
     * AJAX endpoint untuk staff:
     * cek apakah ada order baru setelah ID tertentu (dipakai untuk web notification).
     */
    public function checkLatest(Request $request)
    {
        $user = Auth::user();

        // akss staff
        if (!$user || $user->role !== 'staff') {
            abort(403);
        }

        $afterId = (int) $request->query('after_id', 0);

        // Ambil 1 order terbaru yang ID-nya lebih besar dari after_id
        $order = Order::where('id', '>', $afterId)
            ->orderBy('id', 'asc')
            ->first();

        if (!$order) {
            return response()->json([
                'has_new' => false,
            ]);
        }

        return response()->json([
            'has_new' => true,
            'order'   => [
                'id'            => $order->id,
                'customer_name' => $order->customer_name,
                'pickup_time'   => $order->pickup_time
                    ? $order->pickup_time->format('d M Y H:i')
                    : null,
                'status'        => $order->status,
            ],
        ]);
    }
}
