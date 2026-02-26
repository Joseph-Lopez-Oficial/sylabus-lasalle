<?php

namespace Database\Seeders;

use App\Models\PerformanceLevel;
use Illuminate\Database\Seeder;

class PerformanceLevelSeeder extends Seeder
{
    /**
     * Seed the performance levels catalog.
     *
     * These four levels form the qualitative scale used to assess each
     * evaluation criterion for a given microcurricular learning outcome.
     * They are ordered from the lowest to the highest level of achievement.
     */
    public function run(): void
    {
        $levels = [
            [
                'name' => 'Insuficiente',
                'description' => 'The student does not meet the minimum expected competency. Significant gaps in knowledge, skills, or attitudes are evident.',
                'order' => 1,
            ],
            [
                'name' => 'Básico',
                'description' => 'The student meets the minimum expected competency with limited depth. Performance is acceptable but lacks consistency or breadth.',
                'order' => 2,
            ],
            [
                'name' => 'Competente',
                'description' => 'The student consistently demonstrates the expected competency with solid understanding and reliable application.',
                'order' => 3,
            ],
            [
                'name' => 'Destacado',
                'description' => 'The student exceeds expectations, demonstrating outstanding mastery, critical thinking, and the ability to transfer competencies to novel situations.',
                'order' => 4,
            ],
        ];

        foreach ($levels as $level) {
            PerformanceLevel::firstOrCreate(
                ['name' => $level['name']],
                [
                    'description' => $level['description'],
                    'order' => $level['order'],
                ],
            );
        }
    }
}
