<?php

namespace App\Http\Requests\Professor;

use Illuminate\Foundation\Http\FormRequest;

class ApplyInstitutionalAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The token names the upload kept aside during the import, so the professor
     * confirms the very file whose differences they were shown.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'No se recibió el archivo de la importación.',
            'token.uuid' => 'La referencia del archivo importado no es válida.',
        ];
    }
}
