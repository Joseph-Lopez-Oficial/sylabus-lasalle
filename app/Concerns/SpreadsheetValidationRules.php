<?php

namespace App\Concerns;

trait SpreadsheetValidationRules
{
    /**
     * Get the validation rules used to validate an uploaded spreadsheet.
     *
     * A plain CSV is sniffed as `text/plain`, which `mimes:csv` rejects, so the
     * accepted MIME types are listed explicitly while `extensions` keeps the
     * upload restricted to real spreadsheet extensions.
     *
     * @return array<int, string>
     */
    protected function spreadsheetRules(): array
    {
        return [
            'required',
            'file',
            'extensions:xlsx,xls,csv',
            'mimetypes:'.implode(',', [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'application/octet-stream',
                'text/csv',
                'text/plain',
            ]),
            'max:10240',
        ];
    }

    /**
     * Get the shared error messages for a spreadsheet upload.
     *
     * @return array<string, string>
     */
    protected function spreadsheetMessages(): array
    {
        return [
            'file.required' => 'Debe seleccionar un archivo para importar.',
            'file.extensions' => 'El archivo debe ser un Excel (.xlsx, .xls) o CSV.',
            'file.mimetypes' => 'El archivo debe ser un Excel (.xlsx, .xls) o CSV.',
            'file.max' => 'El archivo no puede superar los 10 MB.',
        ];
    }
}
