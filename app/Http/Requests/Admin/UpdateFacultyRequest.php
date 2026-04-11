<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $facultyId = $this->route('faculty')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', "unique:faculties,code,{$facultyId}"],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la facultad es obligatorio.',
            'code.required' => 'El código de la facultad es obligatorio.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'code.unique' => 'Ya existe una facultad con ese código.',
        ];
    }
}
