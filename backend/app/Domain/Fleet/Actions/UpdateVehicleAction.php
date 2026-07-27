<?php

namespace App\Domain\Fleet\Actions;

use App\Domain\Fleet\Models\Vehicle;

class UpdateVehicleAction
{
    public function execute(Vehicle $vehicle, array $data): Vehicle
    {
        $vehicle->update($data);

        return $vehicle;
    }
}
