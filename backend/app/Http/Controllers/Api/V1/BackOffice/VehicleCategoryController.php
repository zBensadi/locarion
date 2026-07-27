<?php

namespace App\Http\Controllers\Api\V1\BackOffice;

use App\Domain\PlatformAdmin\Models\VehicleCategory;
use App\Domain\PlatformAdmin\Resources\VehicleCategoryResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class VehicleCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', VehicleCategory::class);

        return VehicleCategoryResource::collection(VehicleCategory::orderBy('name')->get());
    }
}
