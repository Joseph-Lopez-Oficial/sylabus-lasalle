<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'document_number' => ['required', 'string', 'max:30', 'unique:professors,document_number'],
            'institutional_email' => ['required', 'email', 'max:191', 'unique:professors,institutional_email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'create_user' => ['boolean'],
        ];

        if ($this->boolean('create_user')) {
            $rules['user_email'] = ['required', 'email', 'max:191', 'unique:users,email'];
            $rules['user_password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
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
            'user_email.required' => 'El correo de acceso al sistema es obligatorio.',
            'user_email.unique' => 'Ya existe un usuario con ese correo.',
            'user_password.required' => 'La contraseña es obligatoria.',
            'user_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'user_password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }
}
