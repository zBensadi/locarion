<?php

namespace App\Http\Controllers\Api\V1\BackOffice;

use App\Domain\Fleet\Actions\CreateReservationAction;
use App\Domain\Fleet\Actions\UpdateReservationStatusAction;
use App\Domain\Fleet\Models\Reservation;
use App\Domain\Fleet\Requests\ReservationRequest;
use App\Domain\Fleet\Requests\UpdateReservationStatusRequest;
use App\Domain\Fleet\Resources\ReservationResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReservationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Reservation::class);

        return ReservationResource::collection(
            Reservation::with(['vehicle.category'])->latest()->get()
        );
    }

    public function store(ReservationRequest $request, CreateReservationAction $action): ReservationResource
    {
        Gate::authorize('create', Reservation::class);

        $reservation = $action->execute($request->validated());

        return new ReservationResource($reservation->load('vehicle'));
    }

    public function show(Reservation $reservation): ReservationResource
    {
        Gate::authorize('view', $reservation);

        return new ReservationResource($reservation->load('vehicle'));
    }

    public function updateStatus(UpdateReservationStatusRequest $request, Reservation $reservation, UpdateReservationStatusAction $action): ReservationResource
    {
        Gate::authorize('update', $reservation);

        $reservation = $action->execute($reservation, $request->validated('status'));

        return new ReservationResource($reservation->load('vehicle'));
    }

    public function destroy(Reservation $reservation): Response
    {
        Gate::authorize('delete', $reservation);

        $reservation->delete();

        return response()->noContent();
    }
}
