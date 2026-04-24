<?php

namespace App\Imports;

use App\Models\Enrollment;
use App\Models\Programming;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProfessorEnrollmentsImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array{row: int, status: string, message: string}> */
    public array $results = [];

    public function __construct(public readonly Programming $programming) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $documentNumber = trim((string) ($row['documento'] ?? $row['document_number'] ?? ''));
            $firstName = trim((string) ($row['nombres'] ?? $row['first_name'] ?? ''));
            $lastName = trim((string) ($row['apellidos'] ?? $row['last_name'] ?? ''));
            $email = trim((string) ($row['correo'] ?? $row['email'] ?? ''));
            $phone = trim((string) ($row['telefono'] ?? $row['phone'] ?? '')) ?: null;

            if (! $documentNumber) {
                $this->results[] = [
                    'row' => $rowNumber,
                    'status' => 'error',
                    'message' => 'El campo documento es obligatorio.',
                ];

                continue;
            }

            $student = Student::where('document_number', $documentNumber)->where('is_active', true)->first();

            if (! $student) {
                if (! $firstName || ! $lastName || ! $email) {
                    $this->results[] = [
                        'row' => $rowNumber,
                        'status' => 'error',
                        'message' => "El estudiante {$documentNumber} no existe y faltan campos obligatorios para crearlo (nombres, apellidos, correo).",
                    ];

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

                $student = Student::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'document_number' => $documentNumber,
                    'email' => $email,
                    'phone' => $phone,
                    'is_active' => true,
                ]);

                $this->results[] = [
                    'row' => $rowNumber,
                    'status' => 'created',
                    'message' => "Estudiante {$documentNumber} creado e inscrito exitosamente.",
                ];
            } else {
                $alreadyEnrolled = Enrollment::where('programming_id', $this->programming->id)
                    ->where('student_id', $student->id)
                    ->exists();

                if ($alreadyEnrolled) {
                    $this->results[] = [
                        'row' => $rowNumber,
                        'status' => 'skipped',
                        'message' => "El estudiante {$documentNumber} ya estaba inscrito.",
                    ];

                    continue;
                }

                $this->results[] = [
                    'row' => $rowNumber,
                    'status' => 'enrolled',
                    'message' => "Estudiante {$documentNumber} inscrito exitosamente.",
                ];
            }

            Enrollment::create([
                'programming_id' => $this->programming->id,
                'student_id' => $student->id,
                'enrolled_at' => now()->toDateString(),
                'is_active' => true,
            ]);
        }
    }
}
