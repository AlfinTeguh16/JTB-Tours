<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'customer_name',
        'email',
        'phone',
        'pickup_time',
        'arrival_time',
        'estimated_duration_minutes',
        'passengers',
        'pickup_location',
        'destination',
        'product_id',
        'adults',
        'children',
        'babies',
        'vehicle_count',
        'note',
        'created_by',
        'status',
    ];

    protected $casts = [
        'pickup_time' => 'datetime',
        'arrival_time' => 'datetime',
        'estimated_duration_minutes' => 'integer',
        'passengers' => 'integer',
        'adults' => 'integer',
        'children' => 'integer',
        'babies' => 'integer',
        'vehicle_count' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
