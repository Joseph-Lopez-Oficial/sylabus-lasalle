<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_space_id' => ['required', 'integer', 'exists:academic_spaces,id'],
            'name' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_space_id.required' => 'Debe seleccionar un espacio académico.',
            'academic_space_id.exists' => 'El espacio académico seleccionado no existe.',
            'name.required' => 'El nombre del tema es obligatorio.',
            'order.required' => 'El orden del tema es obligatorio.',
            'order.min' => 'El orden debe ser al menos 1.',
        ];
    }
}
