<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMesocurricularLearningOutcomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'competency_id' => ['required', 'integer', 'exists:competencies,id'],
            'description' => ['required', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'competency_id.required' => 'Debe seleccionar una competencia.',
            'competency_id.exists' => 'La competencia seleccionada no existe.',
            'description.required' => 'La descripción del resultado mesocurricular es obligatoria.',
        ];
    }
}
