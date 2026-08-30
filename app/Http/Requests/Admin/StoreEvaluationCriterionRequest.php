<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $typeId = $this->input('microcurricular_learning_outcome_type_id');

        return [
            'microcurricular_learning_outcome_type_id' => [
                'required', 'integer', 'exists:microcurricular_learning_outcome_types,id',
            ],
            // Name and order are unique within the type, not across the whole
            // catalogue: each type has its own sequence of criteria.
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('evaluation_criteria', 'name')
                    ->where('microcurricular_learning_outcome_type_id', $typeId),
            ],
            'description' => ['nullable', 'string'],
            'order' => [
                'required', 'integer', 'min:1',
                Rule::unique('evaluation_criteria', 'order')
                    ->where('microcurricular_learning_outcome_type_id', $typeId),
            ],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'microcurricular_learning_outcome_type_id.required' => 'Debe seleccionar un tipo de resultado de aprendizaje.',
            'microcurricular_learning_outcome_type_id.exists' => 'El tipo de resultado seleccionado no existe.',
            'name.required' => 'El nombre del criterio es obligatorio.',
            'name.unique' => 'Ya existe un criterio con ese nombre en el mismo tipo de resultado.',
            'order.required' => 'El orden del criterio es obligatorio.',
            'order.unique' => 'Ya existe un criterio con ese orden en el mismo tipo de resultado.',
            'order.min' => 'El orden debe ser al menos 1.',
        ];
    }
}
