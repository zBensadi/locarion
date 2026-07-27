<?php

namespace App\Domain\PlatformAdmin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handled by middleware/policies
    }

    public function rules(): array
    {
        $categoryId = $this->route('category') ? $this->route('category')->id : null;

        return [
            'name' => 'required|string|max:255|unique:vehicle_categories,name,' . $categoryId,
            'description' => 'nullable|string',
        ];
    }
}
