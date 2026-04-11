<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProblematicNucleusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'program_id.required' => 'Debe seleccionar un programa.',
            'program_id.exists' => 'El programa seleccionado no existe.',
            'name.required' => 'El nombre del núcleo problemático es obligatorio.',
        ];
    }
}
