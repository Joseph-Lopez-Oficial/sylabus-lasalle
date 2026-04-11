<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activity_id' => ['required', 'integer', 'exists:activities,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'activity_id.required' => 'Debe seleccionar una actividad.',
            'activity_id.exists' => 'La actividad seleccionada no existe.',
            'name.required' => 'El nombre del producto es obligatorio.',
            'order.required' => 'El orden del producto es obligatorio.',
            'order.min' => 'El orden debe ser al menos 1.',
        ];
    }
}
