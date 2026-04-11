<?php

namespace App\Imports;

use App\Models\Enrollment;
use App\Models\Programming;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EnrollmentsImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array{row: int, status: string, message: string}> */
    public array $results = [];

    public function __construct(public readonly Programming $programming) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $documentNumber = trim((string) ($row['documento'] ?? $row['document_number'] ?? ''));

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
                $this->results[] = [
                    'row' => $rowNumber,
                    'status' => 'error',
                    'message' => "No existe ningún estudiante activo con documento {$documentNumber}.",
                ];

                continue;
            }

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

            Enrollment::create([
                'programming_id' => $this->programming->id,
                'student_id' => $student->id,
                'enrolled_at' => now()->toDateString(),
                'is_active' => true,
            ]);

            $this->results[] = [
                'row' => $rowNumber,
                'status' => 'created',
                'message' => "Estudiante {$documentNumber} inscrito exitosamente.",
            ];
        }
    }
}
