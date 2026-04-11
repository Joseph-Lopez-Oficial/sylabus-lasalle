<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'competency_id' => ['required', 'integer', 'exists:competencies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:academic_spaces,code'],
            'credits' => ['required', 'integer', 'min:1', 'max:255'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'competency_id.required' => 'Debe seleccionar una competencia.',
            'competency_id.exists' => 'La competencia seleccionada no existe.',
            'name.required' => 'El nombre del espacio académico es obligatorio.',
            'code.required' => 'El código del espacio académico es obligatorio.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'code.unique' => 'Ya existe un espacio académico con ese código.',
            'credits.required' => 'El número de créditos es obligatorio.',
            'credits.min' => 'El número de créditos debe ser al menos 1.',
        ];
    }
}
