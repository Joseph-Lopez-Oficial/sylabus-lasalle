<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $professorId = $this->route('professor')?->id;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'document_number' => ['required', 'string', 'max:30', "unique:professors,document_number,{$professorId}"],
            'institutional_email' => ['required', 'email', 'max:191', "unique:professors,institutional_email,{$professorId}"],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre del profesor es obligatorio.',
            'last_name.required' => 'El apellido del profesor es obligatorio.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.unique' => 'Ya existe un profesor con ese número de documento.',
            'institutional_email.required' => 'El correo institucional es obligatorio.',
            'institutional_email.unique' => 'Ya existe un profesor con ese correo institucional.',
        ];
    }
}
