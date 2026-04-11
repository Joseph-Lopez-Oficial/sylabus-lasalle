<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMicrocurricularLearningOutcomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_space_id' => ['required', 'integer', 'exists:academic_spaces,id'],
            'type_id' => ['required', 'integer', 'exists:microcurricular_learning_outcome_types,id'],
            'mesocurricular_learning_outcome_id' => ['nullable', 'integer', 'exists:mesocurricular_learning_outcomes,id'],
            'description' => ['required', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_space_id.required' => 'Debe seleccionar un espacio académico.',
            'academic_space_id.exists' => 'El espacio académico seleccionado no existe.',
            'type_id.required' => 'Debe seleccionar un tipo de resultado.',
            'type_id.exists' => 'El tipo de resultado seleccionado no existe.',
            'mesocurricular_learning_outcome_id.exists' => 'El resultado mesocurricular seleccionado no existe.',
            'description.required' => 'La descripción del resultado microcurricular es obligatoria.',
        ];
    }
}
