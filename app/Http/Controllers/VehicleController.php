<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    /**
     * List vehicles with pagination.
     */
    public function index(Request $request)
    {
        try {
            $q = Vehicle::query();

            if ($request->filled('search')) {
                $s = $request->search;
                $q->where(function ($w) use ($s) {
                    $w->where('brand', 'like', "%{$s}%")
                      ->orWhere('type', 'like', "%{$s}%")
                      ->orWhere('plate_number', 'like', "%{$s}%");
                });
            }

            if ($request->filled('status')) {
                $q->where('status', $request->status);
            }

            $vehicles = $q->orderBy('brand')->paginate(20)->withQueryString();

            return view('vehicles.index', compact('vehicles'));
        } catch (\Throwable $e) {
            Log::error('Vehicle.index error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString(), 'query'=>$request->all()]);
            return redirect()->back()->with('error','Gagal memuat daftar kendaraan.');
        }
    }

    /**
     * Show create form.
     */
    public function create()
    {
        try {
            return view('vehicles.create');
        } catch (\Throwable $e) {
            Log::error('Vehicle.create error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka form tambah kendaraan.');
        }
    }

    /**
     * Store new vehicle.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'brand'=>'required|string|max:255',
            'type'=>'required|string|max:100',
            'plate_number'=>'required|string|max:50|unique:vehicles,plate_number',
            'color'=>'nullable|string|max:50',
            'status'=>['required', Rule::in(['available','in_use','maintenance'])],
            'year'=>'nullable|integer|min:1900|max:'.(date('Y')+1),
            'capacity'=>'required|integer|min:1'
        ]);

        DB::beginTransaction();
        try {
            Vehicle::create($data);
            DB::commit();
            return redirect()->route('vehicles.index')->with('success','Kendaraan ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Vehicle.store error: '.$e->getMessage(), ['payload'=>$data, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error','Gagal menambahkan kendaraan.');
        }
    }

    /**
     * Show edit form.
     */
    public function edit(Vehicle $vehicle)
    {
        try {
            return view('vehicles.edit', compact('vehicle'));
        } catch (\Throwable $e) {
            Log::error('Vehicle.edit error: '.$e->getMessage(), ['vehicle_id'=>$vehicle->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka form edit kendaraan.');
        }
    }

    /**
     * Update vehicle.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'brand'=>'required|string|max:255',
            'type'=>'required|string|max:100',
            'plate_number'=>['required','string','max:50', Rule::unique('vehicles','plate_number')->ignore($vehicle->id)],
            'color'=>'nullable|string|max:50',
            'status'=>['required', Rule::in(['available','in_use','maintenance'])],
            'year'=>'nullable|integer|min:1900|max:'.(date('Y')+1),
            'capacity'=>'required|integer|min:1'
        ]);

        DB::beginTransaction();
        try {
            $vehicle->update($data);
            DB::commit();
            return redirect()->route('vehicles.index')->with('success','Kendaraan diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Vehicle.update error: '.$e->getMessage(), ['vehicle_id'=>$vehicle->id, 'payload'=>$data, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error','Gagal memperbarui kendaraan.');
        }
    }

    /**
     * Destroy vehicle. Prevent deletion if vehicle is referenced by active assignments/orders.
     */
    public function destroy(Vehicle $vehicle)
    {
        DB::beginTransaction();
        try {
            // Try to prevent deletion if related records exist.
            // Use method_exists to avoid fatal if relations are not defined.
            $hasRelations = false;

            // check assignments relation
            if (method_exists($vehicle, 'assignments')) {
                try {
                    if ($vehicle->assignments()->exists()) $hasRelations = true;
                } catch (\Throwable $e) {
                    // ignore relation check errors, we'll rely on DB constraints
                }
            }

            // check orders relation
            if (!$hasRelations && method_exists($vehicle, 'orders')) {
                try {
                    if ($vehicle->orders()->exists()) $hasRelations = true;
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            if ($hasRelations) {
                return redirect()->back()->with('error','Tidak dapat menghapus kendaraan yang masih terkait assignment atau order.');
            }

            $vehicle->delete();
            DB::commit();
            return redirect()->route('vehicles.index')->with('success','Kendaraan dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Vehicle.destroy error: '.$e->getMessage(), ['vehicle_id'=>$vehicle->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal menghapus kendaraan.');
        }
    }
    /**
     * Show vehicle details including usage history.
     */
    public function show(Vehicle $vehicle)
    {
        try {
            // Fetch history: assignments related to this vehicle
            // We want to see who used it (driver) and when.
            $history = \App\Models\Assignment::with(['driver', 'order'])
                        ->where('vehicle_id', $vehicle->id)
                        ->orderBy('assigned_at', 'desc')
                        ->paginate(20);

            // Check current active assignment to see if "in_use" and who is driving.
            $activeAssignment = \App\Models\Assignment::with(['driver', 'order'])
                                ->where('vehicle_id', $vehicle->id)
                                ->whereIn('status', ['accepted', 'pending']) // pending is effectively reserved
                                ->orderBy('assigned_at', 'asc')
                                ->first();

            return view('vehicles.show', compact('vehicle', 'history', 'activeAssignment'));
        } catch (\Throwable $e) {
            Log::error('Vehicle.show error: '.$e->getMessage(), ['vehicle_id'=>$vehicle->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal memuat detail kendaraan.');
        }
    }
    /**
     * Check which vehicle types are available for a specific time slot.
     */
    public function checkAvailabilityTypes(Request $request)
    {
        $pickup = $request->query('pickup_time');
        $duration = (int) $request->query('duration', 60);

        if (!$pickup) {
             return response()->json([]);
        }

        try {
            $startTime = \Carbon\Carbon::parse($pickup);
            $endTime = $startTime->copy()->addMinutes($duration);

            // Re-use Logic from checkAvailabilityList but return types
            // However, to avoid code duplication, I should isolate the query logic.
            // For now, I'll just check availability same way.

            $availableVehicles = $this->getAvailableVehicles($startTime, $endTime);
            $types = $availableVehicles->pluck('type')->unique()->values()->all();
            sort($types);

            return response()->json($types);

        } catch (\Throwable $e) {
            Log::error('Vehicle.checkAvailabilityTypes error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Get list of specific available vehicles for a time slot.
     */
    public function checkAvailabilityList(Request $request)
    {
        $startStr = $request->query('start');
        $endStr = $request->query('end');

        if (!$startStr || !$endStr) {
             return response()->json([]);
        }

        try {
            $startTime = \Carbon\Carbon::parse($startStr);
            $endTime = \Carbon\Carbon::parse($endStr);

            $availableVehicles = $this->getAvailableVehicles($startTime, $endTime);

            return response()->json($availableVehicles->values()); // reset keys

        } catch (\Throwable $e) {
            Log::error('Vehicle.checkAvailabilityList error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    private function getAvailableVehicles($startTime, $endTime)
    {
        // 1. Get all active vehicles
        $vehicles = Vehicle::where('status', '!=', 'maintenance')->get();
        
        $available = $vehicles->filter(function ($v) use ($startTime, $endTime) {
            // Check 1: Overlapping Assignments (legacy & confirmed assignments)
            // (assuming assignments table has workstart/workend or linked to order time)
            // My previous check used Order time via Assignment relation.
            
            $hasAssignmentOverlap = $v->assignments()
                ->whereIn('status', ['pending', 'accepted', 'in_progress'])
                ->whereHas('order', function ($q) use ($startTime, $endTime) {
                    $q->where(function ($sub) use ($startTime, $endTime) {
                        // Overlap: Order Start < Req End AND Order End > Req Start
                        // Order End = pickup + duration
                        $sub->where('pickup_time', '<', $endTime)
                            ->whereRaw("DATE_ADD(pickup_time, INTERVAL COALESCE(estimated_duration_minutes, 60) MINUTE) > ?", [$startTime]);
                    });
                })
                ->exists();

            if ($hasAssignmentOverlap) return false;

            // Check 2: Overlapping Order Reservations (via order_vehicle)
            // This is new. Check if this vehicle is attached to any Order that overlaps.
            // But we must exclude orders that are already assigned?
            // If an order is assigned, it has an Assignment. If we checked Assignment overlap above, do we double count?
            // If an order is assigned to THIS vehicle, `assignment.vehicle_id` matches.
            // If an order is assigned to ANOTHER vehicle (e.g. swap), then `order_vehicle` might still exist?
            // Ideally, `order_vehicle` is the plan. `assignments` is the execution.
            // If an assignment exists, it takes precedence.
            // If NO assignment exists, check `order_vehicle`.
            
            // Logic: Is there any Order attached to this vehicle that overlaps AND (is pending OR assigned)?
            // If assigned, we already checked assignments? 
            // Actually, if an order is 'assigned', there is an assignment row. 
            // If I rely on `order_vehicle` for 'pending' orders, that covers the gap.
            
            $hasOrderOverlap = $v->orders() // belongsToMany
                ->whereIn('status', ['pending', 'assigned']) // checks pending and assigned orders
                ->where(function ($q) use ($startTime, $endTime) {
                     $q->where('pickup_time', '<', $endTime)
                       ->whereRaw("DATE_ADD(pickup_time, INTERVAL COALESCE(estimated_duration_minutes, 60) MINUTE) > ?", [$startTime]);
                })
                ->exists();
            
            // Note: If an order is assigned, it satisfies $hasOrderOverlap.
            // But does it satisfy $hasAssignmentOverlap?
            // `$v->assignments()` checks `assignments.vehicle_id`.
            // `$v->orders()` checks `order_vehicle.vehicle_id`.
            // Usually they should match. If they don't (e.g. driver changed vehicle),
            // then `assignments` is the truth for execution, but `order_vehicle` might still reserve it erroneously?
            // For now, let's assume strict reservation: if it's in `order_vehicle`, it's busy.
            // Unless the order is cancelled.
            
            if ($hasOrderOverlap) return false;

            return true;
        });

        return $available;
    }
}
