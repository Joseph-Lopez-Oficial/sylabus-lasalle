<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            'activity_type_id' => ['required', 'integer', 'exists:activity_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'topic_id.required' => 'Debe seleccionar un tema.',
            'topic_id.exists' => 'El tema seleccionado no existe.',
            'activity_type_id.required' => 'Debe seleccionar un tipo de actividad.',
            'activity_type_id.exists' => 'El tipo de actividad seleccionado no existe.',
            'name.required' => 'El nombre de la actividad es obligatorio.',
            'order.required' => 'El orden de la actividad es obligatorio.',
            'order.min' => 'El orden debe ser al menos 1.',
        ];
    }
}
