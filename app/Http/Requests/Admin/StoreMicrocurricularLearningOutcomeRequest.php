<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicSpace;
use App\Models\MicrocurricularLearningOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMicrocurricularLearningOutcomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academicSpaceId = (int) $this->input('academic_space_id');

        // Resolve the program through the hierarchy: academic_space → competency → nucleus → program
        $programId = null;
        if ($academicSpaceId) {
            $programId = AcademicSpace::with('competency.problematicNucleus.program')
                ->find($academicSpaceId)
                ?->competency
                ?->problematicNucleus
                ?->program
                ?->id;
        }

        // Collect IDs of outcomes in the same program to check uniqueness
        $siblingsQuery = MicrocurricularLearningOutcome::query()
            ->when($programId, function ($q) use ($programId) {
                $q->whereHas('academicSpace.competency.problematicNucleus', function ($inner) use ($programId) {
                    $inner->where('program_id', $programId);
                });
            });

        return [
            'academic_space_id' => ['required', 'integer', 'exists:academic_spaces,id'],
            'code' => [
                'required',
                'string',
                'max:10',
                'regex:/^RA\d+$/i',
                Rule::unique('microcurricular_learning_outcomes', 'code')
                    ->where(fn ($q) => $q->whereIn('academic_space_id', $siblingsQuery->pluck('academic_space_id')->merge([$academicSpaceId])->unique())),
            ],
            'type_id' => ['required', 'integer', 'exists:microcurricular_learning_outcome_types,id'],
            'mesocurricular_learning_outcome_id' => ['nullable', 'integer', 'exists:mesocurricular_learning_outcomes,id'],
            'description' => ['required', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_space_id.required' => 'Debe seleccionar un espacio académico.',
            'academic_space_id.exists' => 'El espacio académico seleccionado no existe.',
            'code.required' => 'El código del resultado es obligatorio.',
            'code.regex' => 'El código debe tener el formato RA seguido de un número (ej. RA1, RA32).',
            'code.unique' => 'Ya existe un resultado con ese código en el mismo programa académico.',
            'type_id.required' => 'Debe seleccionar un tipo de resultado.',
            'type_id.exists' => 'El tipo de resultado seleccionado no existe.',
            'mesocurricular_learning_outcome_id.exists' => 'El resultado mesocurricular seleccionado no existe.',
            'description.required' => 'La descripción del resultado microcurricular es obligatoria.',
        ];
    }
}
