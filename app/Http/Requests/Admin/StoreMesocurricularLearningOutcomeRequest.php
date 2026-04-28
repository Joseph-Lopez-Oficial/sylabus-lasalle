<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMesocurricularLearningOutcomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $competencyId = $this->input('competency_id');

        $uniqueCode = Rule::unique('mesocurricular_learning_outcomes', 'code')
            ->where('competency_id', $competencyId);

        return [
            'code' => ['required', 'string', 'max:10', 'regex:/^RM\d+$/', $uniqueCode],
            'competency_id' => ['required', 'integer', 'exists:competencies,id'],
            'description' => ['required', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código del resultado es obligatorio.',
            'code.regex' => 'El código debe tener el formato RM seguido de números (ej. RM1, RM15).',
            'code.unique' => 'Ya existe un resultado con ese código en la misma competencia.',
            'competency_id.required' => 'Debe seleccionar una competencia.',
            'competency_id.exists' => 'La competencia seleccionada no existe.',
            'description.required' => 'La descripción del resultado mesocurricular es obligatoria.',
        ];
    }
}
