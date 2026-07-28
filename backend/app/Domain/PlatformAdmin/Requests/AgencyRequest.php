<?php

namespace App\Domain\PlatformAdmin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = $this->route('agency')?->id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('agencies')->ignore($agencyId)],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ];

        if ($this->isMethod('POST')) {
            $rules['admin_email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')];
            $rules['admin_name'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }
}
