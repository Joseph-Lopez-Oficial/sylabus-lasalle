<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgrammingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_space_id' => ['required', 'integer', 'exists:academic_spaces,id'],
            'professor_id' => ['required', 'integer', 'exists:professors,id'],
            'modality_id' => ['required', 'integer', 'exists:modalities,id'],
            'period' => ['required', 'string', 'max:20'],
            'group' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_space_id.required' => 'Debe seleccionar un espacio académico.',
            'academic_space_id.exists' => 'El espacio académico seleccionado no existe.',
            'professor_id.required' => 'Debe seleccionar un profesor.',
            'professor_id.exists' => 'El profesor seleccionado no existe.',
            'modality_id.required' => 'Debe seleccionar una modalidad.',
            'modality_id.exists' => 'La modalidad seleccionada no existe.',
            'period.required' => 'El período académico es obligatorio.',
        ];
    }
}
