<?php

namespace Database\Seeders;

use App\Models\PerformanceLevel;
use Illuminate\Database\Seeder;

class PerformanceLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'name' => 'Insuficiente',
                'description' => 'El estudiante no alcanza el nivel mínimo de competencia esperado. Se evidencian brechas significativas en conocimientos, habilidades o actitudes.',
                'order' => 1,
                'grade_value' => 1.3,
            ],
            [
                'name' => 'Básico',
                'description' => 'El estudiante alcanza el nivel mínimo esperado con profundidad limitada. El desempeño es aceptable pero carece de consistencia o amplitud.',
                'order' => 2,
                'grade_value' => 2.5,
                'is_below_basic_threshold' => true,
            ],
            [
                'name' => 'Competente',
                'description' => 'El estudiante demuestra de forma consistente la competencia esperada con comprensión sólida y aplicación confiable.',
                'order' => 3,
                'grade_value' => 3.8,
            ],
            [
                'name' => 'Destacado',
                'description' => 'El estudiante supera las expectativas, demostrando dominio sobresaliente, pensamiento crítico y capacidad para transferir competencias a situaciones nuevas.',
                'order' => 4,
                'grade_value' => 5.0,
            ],
        ];

        foreach ($levels as $level) {
            PerformanceLevel::firstOrCreate(
                ['name' => $level['name']],
                [
                    'description' => $level['description'],
                    'order' => $level['order'],
                    'grade_value' => $level['grade_value'],
                    'is_below_basic_threshold' => $level['is_below_basic_threshold'] ?? false,
                ],
            );
        }
    }
}
