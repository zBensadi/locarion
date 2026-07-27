<?php

namespace App\Http\Controllers\Api\V1\BackOffice;

use App\Domain\Fleet\Actions\CreateVehicleAction;
use App\Domain\Fleet\Actions\UpdateVehicleAction;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fleet\Requests\VehicleRequest;
use App\Domain\Fleet\Resources\VehicleResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class VehicleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Vehicle::class);

        return VehicleResource::collection(Vehicle::with('category')->latest()->get());
    }

    public function store(VehicleRequest $request, CreateVehicleAction $action): VehicleResource
    {
        Gate::authorize('create', Vehicle::class);
        $vehicle = $action->execute($request->validated());

        return new VehicleResource($vehicle->load('category'));
    }

    public function show(Vehicle $vehicle): VehicleResource
    {
        Gate::authorize('view', $vehicle);

        return new VehicleResource($vehicle->load('category'));
    }

    public function update(VehicleRequest $request, Vehicle $vehicle, UpdateVehicleAction $action): VehicleResource
    {
        Gate::authorize('update', $vehicle);
        $vehicle = $action->execute($vehicle, $request->validated());

        return new VehicleResource($vehicle->load('category'));
    }

    public function destroy(Vehicle $vehicle): Response
    {
        Gate::authorize('delete', $vehicle);
        $vehicle->delete();

        return response()->noContent();
    }
}
