<?php

namespace App\Domain\PlatformAdmin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handled by middleware/policies
    }

    public function rules(): array
    {
        $categoryId = $this->route('category') ? $this->route('category')->id : null;

        $uniqueRule = Rule::unique('vehicle_categories', 'name');
        if ($categoryId) {
            $uniqueRule->ignore($categoryId);
        }

        return [
            'name' => ['required', 'string', 'max:255', $uniqueRule],
            'description' => ['nullable', 'string'],
        ];
    }
}
