<?php

namespace Database\Seeders;

use App\Models\EvaluationCriterion;
use App\Models\MicrocurricularLearningOutcomeType;
use Illuminate\Database\Seeder;

class EvaluationCriterionSeeder extends Seeder
{
    public function run(): void
    {
        $criteriaByType = [
            'Conocimiento' => [
                ['name' => 'Comprensión Conceptual', 'order' => 1],
                ['name' => 'Aplicación de Conocimientos', 'order' => 2],
                ['name' => 'Análisis', 'order' => 3],
                ['name' => 'Dominio del Vocabulario Específico', 'order' => 4],
            ],
            'Habilidad' => [
                ['name' => 'Dominio del Procedimiento', 'order' => 1],
                ['name' => 'Adaptabilidad', 'order' => 2],
                ['name' => 'Eficacia en la Ejecución', 'order' => 3],
            ],
            'Actitud' => [
                ['name' => 'Compromiso y Responsabilidad', 'order' => 1],
                ['name' => 'Colaboración y Trabajo en Equipo', 'order' => 2],
                ['name' => 'Respeto', 'order' => 3],
            ],
        ];

        foreach ($criteriaByType as $typeName => $criteria) {
            $type = MicrocurricularLearningOutcomeType::where('name', $typeName)->first();

            if (! $type) {
                continue;
            }

            foreach ($criteria as $criterion) {
                EvaluationCriterion::firstOrCreate(
                    [
                        'microcurricular_learning_outcome_type_id' => $type->id,
                        'name' => $criterion['name'],
                    ],
                    ['order' => $criterion['order'], 'is_active' => true],
                );
            }
        }
    }
}
