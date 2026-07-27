<?php

namespace App\Domain\PlatformAdmin\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleCategory extends Model
{
    /** @use HasFactory<\Database\Factories\VehicleCategoryFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
    ];
}
