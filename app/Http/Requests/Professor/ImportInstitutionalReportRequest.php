<?php

namespace App\Http\Requests\Professor;

use App\Concerns\SpreadsheetValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class ImportInstitutionalReportRequest extends FormRequest
{
    use SpreadsheetValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => $this->spreadsheetRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->spreadsheetMessages();
    }
}
