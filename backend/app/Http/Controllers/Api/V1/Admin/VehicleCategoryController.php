<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\PlatformAdmin\Models\VehicleCategory;
use App\Domain\PlatformAdmin\Requests\VehicleCategoryRequest;
use App\Domain\PlatformAdmin\Resources\VehicleCategoryResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class VehicleCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', VehicleCategory::class);

        return VehicleCategoryResource::collection(VehicleCategory::latest()->get());
    }

    public function store(VehicleCategoryRequest $request): VehicleCategoryResource
    {
        Gate::authorize('create', VehicleCategory::class);
        $category = VehicleCategory::create($request->validated());

        return new VehicleCategoryResource($category);
    }

    public function show(VehicleCategory $category): VehicleCategoryResource
    {
        Gate::authorize('viewAny', VehicleCategory::class);

        return new VehicleCategoryResource($category);
    }

    public function update(VehicleCategoryRequest $request, VehicleCategory $category): VehicleCategoryResource
    {
        Gate::authorize('update', $category);
        $category->update($request->validated());

        return new VehicleCategoryResource($category);
    }

    public function destroy(VehicleCategory $category): Response
    {
        Gate::authorize('delete', $category);
        $category->delete();

        return response()->noContent();
    }
}
