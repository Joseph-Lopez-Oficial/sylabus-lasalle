<?php

namespace App\Http\Requests\Professor;

use Illuminate\Foundation\Http\FormRequest;

class SaveAcademicSpaceAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Every answer is optional: writing the analysis is not required to grade or
     * to consult statistics, so a professor may save partial work and come back
     * to it later.
     */
    public function rules(): array
    {
        return [
            'analyses' => ['required', 'array', 'min:1'],
            'analyses.*.microcurricular_learning_outcome_id' => [
                'required', 'integer', 'exists:microcurricular_learning_outcomes,id',
            ],
            'analyses.*.outcome_performance' => ['nullable', 'string', 'max:5000'],
            'analyses.*.academic_space_performance' => ['nullable', 'string', 'max:5000'],
            'analyses.*.improvement_proposals' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'analyses.required' => 'Debe enviar al menos un análisis.',
            'analyses.*.microcurricular_learning_outcome_id.required' => 'Cada análisis debe indicar el resultado de aprendizaje.',
            'analyses.*.microcurricular_learning_outcome_id.exists' => 'Uno de los resultados de aprendizaje no existe.',
            'analyses.*.outcome_performance.max' => 'La descripción del desempeño frente al resultado no puede superar los 5000 caracteres.',
            'analyses.*.academic_space_performance.max' => 'La descripción del desempeño frente al espacio académico no puede superar los 5000 caracteres.',
            'analyses.*.improvement_proposals.max' => 'El análisis y las propuestas de mejora no pueden superar los 5000 caracteres.',
        ];
    }
}
