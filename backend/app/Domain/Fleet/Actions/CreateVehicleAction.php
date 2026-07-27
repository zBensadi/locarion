<?php

namespace App\Domain\Fleet\Actions;

use App\Domain\Fleet\Models\Vehicle;

class CreateVehicleAction
{
    public function execute(array $data): Vehicle
    {
        return Vehicle::create($data);
    }
}
