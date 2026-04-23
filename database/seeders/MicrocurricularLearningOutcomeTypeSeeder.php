<?php

namespace Database\Seeders;

use App\Models\MicrocurricularLearningOutcomeType;
use Illuminate\Database\Seeder;

class MicrocurricularLearningOutcomeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Conocimiento',
                'description' => 'Resultados relacionados con la adquisición y comprensión de conceptos, hechos y marcos teóricos (Saber Conocer).',
            ],
            [
                'name' => 'Habilidad',
                'description' => 'Resultados relacionados con el desarrollo de habilidades prácticas y competencias procedimentales (Saber Hacer).',
            ],
            [
                'name' => 'Actitud',
                'description' => 'Resultados relacionados con la formación de valores, actitudes y disposiciones personales (Saber Ser).',
            ],
        ];

        foreach ($types as $type) {
            MicrocurricularLearningOutcomeType::firstOrCreate(
                ['name' => $type['name']],
                ['description' => $type['description']],
            );
        }
    }
}
