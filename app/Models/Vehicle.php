<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'brand',
        'type',
        'plate_number',
        'color',
        'status',
        'year',
        'capacity',
    ];

    protected $casts = [
        'year' => 'integer',
        'capacity' => 'integer',
    ];

    // Scope helper
    public function scopeAvailable($query, $start = null, $durationMinutes = 0)
    {
        if (!$start) {
            return $query->where('status', 'available');
        }

        $startTime = \Carbon\Carbon::parse($start);
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        // Filter vehicles that do NOT have overlapping assignments
        // And are not in maintenance
        return $query->where('status', '!=', 'maintenance')
                     ->whereDoesntHave('assignments', function ($q) use ($startTime, $endTime) {
            $q->whereIn('status', ['accepted', 'in_progress'])
              ->whereHas('order', function ($orderQ) use ($startTime, $endTime) {
                  $orderQ->where(function ($sub) use ($startTime, $endTime) {
                       // Overlap: Order Start <= Request End AND Order End >= Request Start
                       $sub->where('pickup_time', '<=', $endTime)
                           ->whereRaw("DATE_ADD(pickup_time, INTERVAL estimated_duration_minutes MINUTE) >= ?", [$startTime]);
                  });
              });
        });
    }

    public function isAvailableAt($start, $durationMinutes)
    {
        $startTime = \Carbon\Carbon::parse($start);
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        $conflicts = $this->assignments()
            ->whereIn('status', ['accepted', 'in_progress'])
            ->whereHas('order', function ($q) use ($startTime, $endTime) {
                // Check overlap based on order times
                 // We need to access order pickup_time and estimated_duration_minutes
                // Note: This logic assumes assignments are linked to orders which hold the time
                $q->where(function($sub) use ($startTime, $endTime) {
                    // Logic: Order Start < Requested End AND Order End > Requested Start
                    // Since specific SQL might be complex here, simpler to fetch overlapping in PHP if collection is small, 
                    // but for Model method we prefer query.
                    // Let's rely on the scope logic above which is more robust for SQL.
                });
            })->count();
            
        // Alternative: Re-use the scope logic on this instance
        // But scope is for Builder.
        
        // Let's implement a direct check
        $conflicting = $this->assignments()
            ->whereIn('status', ['accepted', 'in_progress'])
            ->get()
            ->filter(function($assignment) use ($startTime, $endTime) {
                if (!$assignment->order) return false;
                
                $orderStart = $assignment->order->pickup_time;
                $orderEnd = $orderStart->copy()->addMinutes($assignment->order->estimated_duration_minutes);
                
                return $startTime->lt($orderEnd) && $endTime->gt($orderStart);
            });

        return $conflicting->isEmpty();
    }

    public function getCurrentDriver()
    {
        if ($this->status !== 'in_use') return null;

        return $this->assignments()
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->first()
            ->driver ?? null;
    }

    public function getUsageHistory()
    {
        return $this->assignments()
            ->with(['driver', 'order'])
            ->latest('assigned_at')
            ->get();
    }

    public function assignments() {
        return $this->hasMany(\App\Models\Assignment::class, 'vehicle_id');
    }
    public function orders() {
        return $this->belongsToMany(\App\Models\Order::class);
    }
    
}
