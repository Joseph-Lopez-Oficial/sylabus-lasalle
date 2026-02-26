<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Runs all catalog seeders first (no foreign key dependencies),
     * then seeds the initial admin user account.
     */
    public function run(): void
    {
        $this->call([
            MicrocurricularLearningOutcomeTypeSeeder::class,
            ModalitySeeder::class,
            EvaluationCriterionSeeder::class,
            PerformanceLevelSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
