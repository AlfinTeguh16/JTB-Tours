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
    
            // Filters
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
            }
    
            if ($request->filled('from')) {
                $q->whereDate('pickup_time', '>=', $request->from);
            }
    
            if ($request->filled('to')) {
                $q->whereDate('pickup_time', '<=', $request->to);
            }
    
            // paginate once
            $orders = $q->paginate(25)->withQueryString();
    
            // Produk untuk dropdown filter di view
            $products = Product::orderBy('name')->get();
    
            // Tambahkan formatted fields supaya view lebih sederhana dan analyzer tidak complain
            $orders->getCollection()->transform(function ($o) {
                try {
                    $o->formatted_pickup = $o->pickup_time ? \Carbon\Carbon::parse($o->pickup_time)->format('d M Y H:i') : '-';
                } catch (\Throwable $e) {
                    $o->formatted_pickup = '-';
                }
    
                try {
                    $o->formatted_arrival = $o->arrival_time ? \Carbon\Carbon::parse($o->arrival_time)->format('d M Y H:i') : '-';
                } catch (\Throwable $e) {
                    $o->formatted_arrival = '-';
                }
    
                $o->summary_people = ($o->adults ?? 0) . ' adults · ' . ($o->children ?? 0) . ' children · ' . ($o->babies ?? 0) . ' babies';
                $o->summary_contact = ($o->email ?? '-') . ' · ' . ($o->phone ?? '-');
    
                return $o;
            });
    
            return view('orders.index', compact('orders', 'products'));
        } catch (\Throwable $e) {
            Log::error('Order.index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
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
            $products = Product::orderBy('name')->get();
            return view('orders.create', compact('products'));
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
            'customer_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:25',
            'pickup_time' => 'required|date',
            'arrival_time' => 'nullable|date|after_or_equal:pickup_time',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'passengers' => 'required|integer|min:1',
            'pickup_location' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'product_id' => 'required|exists:products,id',
            'adults' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'babies' => 'nullable|integer|min:0',
            'vehicle_count' => 'nullable|integer|min:1',
            'note' => 'nullable|string|max:2000',
        ]);

        DB::beginTransaction();
        try {
            // calculate estimated duration if missing
            $est = $validated['estimated_duration_minutes'] ?? null;
            if (empty($est) && !empty($validated['arrival_time']) && !empty($validated['pickup_time'])) {
                $diffMinutes = max(1, (int) round((strtotime($validated['arrival_time']) - strtotime($validated['pickup_time'])) / 60));
                $validated['estimated_duration_minutes'] = $diffMinutes;
            } elseif (empty($est)) {
                // fallback default 60 minutes if nothing provided
                $validated['estimated_duration_minutes'] = 60;
            }

            $validated['created_by'] = Auth::check() ? Auth::id() : null;
            $validated['status'] = 'pending';

            Order::create($validated);

            DB::commit();

            return redirect()->route('orders.index')->with('success', 'Order berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order.store error: ' . $e->getMessage(), ['payload' => $validated, 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Gagal membuat order.');
        }
    }

    /**
     * Display the specified order.
     * (Note: your UI uses modal details; this method kept for API / direct show if needed)
     */
    public function show(Order $order)
    {
        try {
            $order->load(['product', 'assignments']);
            return view('orders.show', compact('order'));
        } catch (\Throwable $e) {
            Log::error('Order.show error: ' . $e->getMessage(), ['order_id' => $order->id, 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal membuka detail order.');
        }
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order)
    {
        try {
            $products = Product::orderBy('name')->get();
            return view('orders.edit', compact('order', 'products'));
        } catch (\Throwable $e) {
            Log::error('Order.edit error: ' . $e->getMessage(), ['order_id' => $order->id, 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal membuka form edit order.');
        }
    }

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:25',
            'pickup_time' => 'required|date',
            'arrival_time' => 'nullable|date|after_or_equal:pickup_time',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'passengers' => 'required|integer|min:1',
            'pickup_location' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'product_id' => 'required|exists:products,id',
            'adults' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'babies' => 'nullable|integer|min:0',
            'vehicle_count' => 'nullable|integer|min:1',
            'note' => 'nullable|string|max:2000',
        ]);

        DB::beginTransaction();
        try {
            // recalc estimated duration if not provided but arrival_time exists
            $est = $validated['estimated_duration_minutes'] ?? null;
            if (empty($est) && !empty($validated['arrival_time']) && !empty($validated['pickup_time'])) {
                $diffMinutes = max(1, (int) round((strtotime($validated['arrival_time']) - strtotime($validated['pickup_time'])) / 60));
                $validated['estimated_duration_minutes'] = $diffMinutes;
            } elseif (empty($est) && empty($order->estimated_duration_minutes)) {
                $validated['estimated_duration_minutes'] = 60;
            }

            $order->update($validated);

            DB::commit();

            return redirect()->route('orders.index')->with('success', 'Order diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order.update error: ' . $e->getMessage(), ['order_id' => $order->id, 'payload' => $validated, 'trace' => $e->getTraceAsString()]);
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
                return redirect()->back()->with('error', 'Order yang sudah di-assign atau completed tidak dapat dihapus.');
            }

            $order->delete();

            DB::commit();

            return redirect()->route('orders.index')->with('success', 'Order dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order.destroy error: ' . $e->getMessage(), ['order_id' => $order->id, 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal menghapus order.');
        }
    }
}
