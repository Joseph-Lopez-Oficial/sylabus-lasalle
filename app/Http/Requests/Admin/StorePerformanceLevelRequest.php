<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePerformanceLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('performance_levels', 'name')],
            'description' => ['nullable', 'string'],
            'order' => ['required', 'integer', 'min:1', Rule::unique('performance_levels', 'order')],
            'grade_value' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'is_below_basic_threshold' => ['boolean'],
            'is_active' => ['boolean'],
        ];
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
