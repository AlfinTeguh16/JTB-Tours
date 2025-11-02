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
}
