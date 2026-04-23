<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use Illuminate\Database\Seeder;

class ActivityTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Encuentro',
                'description' => 'Sesión sincrónica entre el profesor y los estudiantes, ya sea presencial o virtual en vivo.',
            ],
            [
                'name' => 'Tarea',
                'description' => 'Entregable asincrónico que el estudiante desarrolla de forma autónoma fuera del espacio de clase.',
            ],
            [
                'name' => 'Cuestionario',
                'description' => 'Evaluación de conocimientos mediante preguntas con respuesta estructurada, cerrada o abierta.',
            ],
        ];

        foreach ($types as $type) {
            ActivityType::firstOrCreate(
                ['name' => $type['name']],
                ['description' => $type['description']],
            );
        }
    }
}
