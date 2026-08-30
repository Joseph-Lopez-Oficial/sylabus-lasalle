<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEvaluationCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $criterion = $this->route('evaluationCriterion');
        $typeId = $this->input('microcurricular_learning_outcome_type_id');

        return [
            'microcurricular_learning_outcome_type_id' => [
                'required', 'integer', 'exists:microcurricular_learning_outcome_types,id',
            ],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('evaluation_criteria', 'name')
                    ->where('microcurricular_learning_outcome_type_id', $typeId)
                    ->ignore($criterion?->id),
            ],
            'description' => ['nullable', 'string'],
            'order' => [
                'required', 'integer', 'min:1',
                Rule::unique('evaluation_criteria', 'order')
                    ->where('microcurricular_learning_outcome_type_id', $typeId)
                    ->ignore($criterion?->id),
            ],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $criterion = $this->route('evaluationCriterion');

            if (! $criterion) {
                return;
            }

            $gradesCount = $criterion->grades()->count();

            if ($gradesCount === 0) {
                return;
            }

            // Moving a criterion to another type would leave existing grades
            // pairing an outcome with a criterion of a foreign type, exactly the
            // inconsistency the grading service rejects.
            $newType = (int) $this->input('microcurricular_learning_outcome_type_id');

            if ($newType !== $criterion->microcurricular_learning_outcome_type_id) {
                $validator->errors()->add(
                    'microcurricular_learning_outcome_type_id',
                    "No se puede cambiar el tipo de este criterio porque {$gradesCount} calificación(es) dependen de él."
                );
            }

            // An absent field means the flag is not being touched, not that the
            // criterion should be retired.
            if ($this->has('is_active') && ! $this->boolean('is_active')) {
                $validator->errors()->add(
                    'is_active',
                    "No se puede desactivar este criterio porque {$gradesCount} calificación(es) dependen de él."
                );
            }
        });
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
