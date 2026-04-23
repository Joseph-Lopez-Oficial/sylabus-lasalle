<?php

namespace Database\Seeders;

use App\Models\EvaluationCriterion;
use Illuminate\Database\Seeder;

class EvaluationCriterionSeeder extends Seeder
{
    public function run(): void
    {
        $criteria = [
            [
                'name' => 'Saber Conocer',
                'description' => 'Evalúa la capacidad del estudiante para adquirir, comprender y aplicar conocimientos teóricos y marcos conceptuales.',
                'order' => 1,
            ],
            [
                'name' => 'Saber Hacer',
                'description' => 'Evalúa la capacidad del estudiante para ejecutar tareas, aplicar técnicas y demostrar habilidades prácticas y procedimentales.',
                'order' => 2,
            ],
            [
                'name' => 'Saber Ser',
                'description' => 'Evalúa las actitudes, valores, conducta ética y disposiciones personales del estudiante en contextos académicos y profesionales.',
                'order' => 3,
            ],
            [
                'name' => 'Saber Transferir',
                'description' => 'Evalúa la capacidad del estudiante para transferir y aplicar las competencias aprendidas a situaciones nuevas, reales e interdisciplinares.',
                'order' => 4,
            ],
        ];

        foreach ($criteria as $criterion) {
            EvaluationCriterion::firstOrCreate(
                ['name' => $criterion['name']],
                [
                    'description' => $criterion['description'],
                    'order' => $criterion['order'],
                ],
            );
        }
    }
}
