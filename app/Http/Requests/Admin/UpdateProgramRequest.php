<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $programId = $this->route('program')?->id;

        return [
            'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', "unique:programs,code,{$programId}"],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'faculty_id.required' => 'Debe seleccionar una facultad.',
            'faculty_id.exists' => 'La facultad seleccionada no existe.',
            'name.required' => 'El nombre del programa es obligatorio.',
            'code.required' => 'El código del programa es obligatorio.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'code.unique' => 'Ya existe un programa con ese código.',
        ];
    }
}
