<?php

namespace App\Http\Requests\Admin;

use App\Models\Grade;
use App\Models\PerformanceLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePerformanceLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $levelId = $this->route('performanceLevel')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('performance_levels', 'name')->ignore($levelId)],
            'description' => ['nullable', 'string'],
            'order' => ['required', 'integer', 'min:1', Rule::unique('performance_levels', 'order')->ignore($levelId)],
            'grade_value' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'is_below_basic_threshold' => ['boolean'],
        ];
    }

    /**
     * Guards that need to look beyond the submitted fields.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $level = $this->route('performanceLevel');

            if (! $level) {
                return;
            }

            $this->rejectClearingValueWhenInUse($validator, $level);
            $this->rejectLeavingSystemWithoutThreshold($validator, $level);
        });
    }

    /**
     * A level already used by grades cannot be left without a value, because
     * every average computed from it would silently ignore those grades.
     */
    private function rejectClearingValueWhenInUse(Validator $validator, PerformanceLevel $level): void
    {
        if ($this->input('grade_value') !== null && $this->input('grade_value') !== '') {
            return;
        }

        $gradesCount = Grade::where('performance_level_id', $level->id)->count();

        if ($gradesCount > 0) {
            $validator->errors()->add(
                'grade_value',
                "No se puede dejar sin valor este nivel porque {$gradesCount} calificación(es) lo usan. Asigne un valor entre 0 y 5."
            );
        }
    }

    /**
     * Exactly one level must carry the threshold flag: the report of students
     * at risk is derived from it.
     */
    private function rejectLeavingSystemWithoutThreshold(Validator $validator, PerformanceLevel $level): void
    {
        if ($this->boolean('is_below_basic_threshold')) {
            return;
        }

        $othersWithFlag = PerformanceLevel::query()
            ->whereKeyNot($level->id)
            ->where('is_below_basic_threshold', true)
            ->exists();

        if (! $othersWithFlag) {
            $validator->errors()->add(
                'is_below_basic_threshold',
                'Debe existir un nivel marcado como umbral de bajo rendimiento. Marque otro nivel antes de quitar esta opción.'
            );
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del nivel es obligatorio.',
            'name.unique' => 'Ya existe un nivel de desempeño con ese nombre.',
            'order.required' => 'El orden del nivel es obligatorio.',
            'order.unique' => 'Ya existe un nivel de desempeño con ese orden.',
            'order.min' => 'El orden debe ser al menos 1.',
            'grade_value.numeric' => 'El valor debe ser un número.',
            'grade_value.min' => 'El valor no puede ser menor que 0.',
            'grade_value.max' => 'El valor no puede ser mayor que 5.',
        ];
    }
}
