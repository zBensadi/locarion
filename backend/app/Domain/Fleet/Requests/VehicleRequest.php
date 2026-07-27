<?php

namespace App\Domain\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handled by policies
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle') ? $this->route('vehicle')->id : null;
        $agencyId = auth()->user()->agency_id;

        return [
            'category_id' => 'required|uuid|exists:vehicle_categories,id',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'license_plate' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vehicles')->where(function ($query) use ($agencyId) {
                    return $query->where('agency_id', $agencyId);
                })->ignore($vehicleId),
            ],
            'daily_rate' => 'required|integer|min:0',
            'status' => 'required|string|in:available,reserved,maintenance,retired',
        ];
    }
}
