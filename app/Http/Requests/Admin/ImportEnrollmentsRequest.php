<?php

namespace App\Http\Requests\Admin;

use App\Concerns\SpreadsheetValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class ImportEnrollmentsRequest extends FormRequest
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
