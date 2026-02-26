<?php

namespace Database\Seeders;

use App\Models\EvaluationCriterion;
use Illuminate\Database\Seeder;

class EvaluationCriterionSeeder extends Seeder
{
    /**
     * Seed the evaluation criteria catalog.
     *
     * These four criteria define the dimensions used to assess each
     * microcurricular learning outcome, following the competency-based
     * education model adopted by Universidad de La Salle.
     */
    public function run(): void
    {
        $criteria = [
            [
                'name' => 'Saber Conocer',
                'description' => 'Evaluates the student\'s ability to acquire, understand, and apply theoretical knowledge and conceptual frameworks.',
                'order' => 1,
            ],
            [
                'name' => 'Saber Hacer',
                'description' => 'Evaluates the student\'s ability to perform tasks, apply techniques, and demonstrate practical procedural skills.',
                'order' => 2,
            ],
            [
                'name' => 'Saber Ser',
                'description' => 'Evaluates the student\'s attitudes, values, ethical conduct, and personal dispositions in academic and professional contexts.',
                'order' => 3,
            ],
            [
                'name' => 'Saber Transferir',
                'description' => 'Evaluates the student\'s ability to transfer and apply learned competencies to new, real-world, and interdisciplinary situations.',
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
