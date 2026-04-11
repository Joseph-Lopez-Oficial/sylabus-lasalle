<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'document_number' => ['required', 'string', 'max:30', 'unique:students,document_number'],
            'email' => ['required', 'email', 'max:191', 'unique:students,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre del estudiante es obligatorio.',
            'last_name.required' => 'El apellido del estudiante es obligatorio.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.unique' => 'Ya existe un estudiante con ese número de documento.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Ya existe un estudiante con ese correo.',
        ];
    }
}
