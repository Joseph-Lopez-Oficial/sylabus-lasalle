<?php

namespace App\Http\Requests\Professor;

use App\Concerns\SpreadsheetValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class ImportGradesRequest extends FormRequest
{
    use SpreadsheetValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => $this->spreadsheetRules(),
        ];
    }

    public function messages(): array
    {
        return $this->spreadsheetMessages();
    }
}
