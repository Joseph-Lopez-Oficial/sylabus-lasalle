<?php

namespace Database\Seeders;

use App\Models\Modality;
use Illuminate\Database\Seeder;

class ModalitySeeder extends Seeder
{
    /**
     * Seed the modalities catalog.
     *
     * Modalities define the delivery mode of an academic programming:
     * in-person, fully virtual, or a combination of both.
     */
    public function run(): void
    {
        $modalities = [
            [
                'name' => 'In-Person',
                'description' => 'Classes are held entirely on campus with physical attendance required.',
            ],
            [
                'name' => 'Virtual',
                'description' => 'Classes are conducted entirely online through digital platforms.',
            ],
            [
                'name' => 'Hybrid',
                'description' => 'Classes combine in-person and virtual sessions in a blended format.',
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
