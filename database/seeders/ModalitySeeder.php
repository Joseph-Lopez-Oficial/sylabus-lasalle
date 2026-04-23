<?php

namespace Database\Seeders;

use App\Models\Modality;
use Illuminate\Database\Seeder;

class ModalitySeeder extends Seeder
{
    public function run(): void
    {
        $modalities = [
            [
                'name' => 'Presencial',
                'description' => 'Las clases se realizan completamente en el campus con asistencia física requerida.',
            ],
            [
                'name' => 'Virtual',
                'description' => 'Las clases se desarrollan completamente en línea a través de plataformas digitales.',
            ],
            [
                'name' => 'Híbrida',
                'description' => 'Las clases combinan sesiones presenciales y virtuales en formato mixto.',
            ],
        ];

        foreach ($modalities as $modality) {
            Modality::firstOrCreate(
                ['name' => $modality['name']],
                ['description' => $modality['description']],
            );
        }
    }
}
