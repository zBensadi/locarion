<?php

namespace App\Domain\Fleet\Models;

use App\Domain\PlatformAdmin\Models\VehicleCategory;
use App\Domain\Tenancy\Models\Agency;
use App\Domain\Tenancy\Traits\HasTenancy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    /** @use HasFactory<\Database\Factories\VehicleFactory> */
    use HasFactory, HasTenancy, HasUuids, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'category_id',
        'make',
        'model',
        'year',
        'license_plate',
        'daily_rate',
        'status',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'category_id');
    }
}
