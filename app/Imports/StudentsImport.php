<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array{row: int, status: string, message: string}> */
    public array $results = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $documentNumber = trim((string) ($row['documento'] ?? $row['document_number'] ?? ''));
            $firstName = trim((string) ($row['nombres'] ?? $row['first_name'] ?? ''));
            $lastName = trim((string) ($row['apellidos'] ?? $row['last_name'] ?? ''));
            $email = trim((string) ($row['correo'] ?? $row['email'] ?? ''));

            if (! $documentNumber || ! $firstName || ! $lastName || ! $email) {
                $this->results[] = [
                    'row' => $rowNumber,
                    'status' => 'error',
                    'message' => 'Faltan campos obligatorios (documento, nombres, apellidos, correo).',
                ];

                continue;
            }

            $existing = Student::withTrashed()->where('document_number', $documentNumber)->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                    $existing->update([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'is_active' => true,
                    ]);
                    $this->results[] = ['row' => $rowNumber, 'status' => 'restored', 'message' => "Estudiante {$documentNumber} restaurado."];
                } else {
                    $existing->update([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                    ]);
                    $this->results[] = ['row' => $rowNumber, 'status' => 'updated', 'message' => "Estudiante {$documentNumber} actualizado."];
                }

                continue;
            }

            if (Student::where('email', $email)->exists()) {
                $this->results[] = [
                    'row' => $rowNumber,
                    'status' => 'error',
                    'message' => "El correo {$email} ya está en uso por otro estudiante.",
                ];

                continue;
            }

            Student::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'document_number' => $documentNumber,
                'email' => $email,
                'is_active' => true,
            ]);

            $this->results[] = ['row' => $rowNumber, 'status' => 'created', 'message' => "Estudiante {$documentNumber} creado."];
        }
    }
}
