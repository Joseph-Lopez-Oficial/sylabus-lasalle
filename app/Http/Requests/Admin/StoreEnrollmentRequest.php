<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
                'unique:enrollments,student_id,NULL,id,programming_id,'.$this->route('programming')?->id,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Debe seleccionar un estudiante.',
            'student_id.exists' => 'El estudiante seleccionado no existe.',
            'student_id.unique' => 'El estudiante ya está inscrito en esta programación.',
        ];
    }
}
