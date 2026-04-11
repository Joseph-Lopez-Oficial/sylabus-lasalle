<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompetencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'problematic_nucleus_id' => ['required', 'integer', 'exists:problematic_nuclei,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'problematic_nucleus_id.required' => 'Debe seleccionar un núcleo problemático.',
            'problematic_nucleus_id.exists' => 'El núcleo problemático seleccionado no existe.',
            'name.required' => 'El nombre de la competencia es obligatorio.',
        ];
    }
}
