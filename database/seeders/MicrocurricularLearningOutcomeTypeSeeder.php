<?php

namespace Database\Seeders;

use App\Models\MicrocurricularLearningOutcomeType;
use Illuminate\Database\Seeder;

class MicrocurricularLearningOutcomeTypeSeeder extends Seeder
{
    /**
     * Seed the microcurricular learning outcome types catalog.
     *
     * The three types classify what kind of learning a microcurricular
     * outcome targets: knowledge acquisition, skill development, or
     * attitude/value formation.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Knowledge',
                'description' => 'Outcomes related to the acquisition and understanding of concepts, facts, and theoretical frameworks (Saber Conocer).',
            ],
            [
                'name' => 'Skill',
                'description' => 'Outcomes related to the development of practical abilities and procedural competencies (Saber Hacer).',
            ],
            [
                'name' => 'Attitude',
                'description' => 'Outcomes related to the formation of values, attitudes, and personal dispositions (Saber Ser).',
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
