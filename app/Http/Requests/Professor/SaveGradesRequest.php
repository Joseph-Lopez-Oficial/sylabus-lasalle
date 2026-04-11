<?php

namespace App\Http\Requests\Professor;

use Illuminate\Foundation\Http\FormRequest;

class SaveGradesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'grades.*.microcurricular_learning_outcome_id' => ['required', 'integer', 'exists:microcurricular_learning_outcomes,id'],
            'grades.*.evaluation_criterion_id' => ['required', 'integer', 'exists:evaluation_criteria,id'],
            'grades.*.performance_level_id' => ['required', 'integer', 'exists:performance_levels,id'],
            'grades.*.observations' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'grades.required' => 'Debe enviar al menos una calificación.',
            'grades.*.enrollment_id.required' => 'Cada calificación debe tener una inscripción válida.',
            'grades.*.enrollment_id.exists' => 'Una de las inscripciones no existe.',
            'grades.*.microcurricular_learning_outcome_id.exists' => 'Uno de los resultados microcurriculares no existe.',
            'grades.*.evaluation_criterion_id.exists' => 'Uno de los criterios de evaluación no existe.',
            'grades.*.performance_level_id.exists' => 'Uno de los niveles de desempeño no existe.',
        ];
    }
}
