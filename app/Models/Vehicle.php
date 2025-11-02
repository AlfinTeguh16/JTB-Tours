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
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function assignments() {
        return $this->hasMany(\App\Models\Assignment::class, 'vehicle_id');
    }
    public function orders() {
        return $this->hasMany(\App\Models\Order::class, 'vehicle_id');
    }
    
}
