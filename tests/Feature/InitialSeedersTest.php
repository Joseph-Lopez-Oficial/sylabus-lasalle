<?php

namespace Tests\Feature;

use Database\Seeders\AdminUserSeeder;
use Database\Seeders\EvaluationCriterionSeeder;
use Database\Seeders\MicrocurricularLearningOutcomeTypeSeeder;
use Database\Seeders\ModalitySeeder;
use Database\Seeders\PerformanceLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitialSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_microcurricular_learning_outcome_types_are_seeded(): void
    {
        $this->seed(MicrocurricularLearningOutcomeTypeSeeder::class);

        $this->assertDatabaseCount('microcurricular_learning_outcome_types', 3);
        $this->assertDatabaseHas('microcurricular_learning_outcome_types', ['name' => 'Knowledge']);
        $this->assertDatabaseHas('microcurricular_learning_outcome_types', ['name' => 'Skill']);
        $this->assertDatabaseHas('microcurricular_learning_outcome_types', ['name' => 'Attitude']);
    }

    public function test_modalities_are_seeded(): void
    {
        $this->seed(ModalitySeeder::class);

        $this->assertDatabaseCount('modalities', 3);
        $this->assertDatabaseHas('modalities', ['name' => 'In-Person']);
        $this->assertDatabaseHas('modalities', ['name' => 'Virtual']);
        $this->assertDatabaseHas('modalities', ['name' => 'Hybrid']);
    }

    public function test_evaluation_criteria_are_seeded(): void
    {
        $this->seed(EvaluationCriterionSeeder::class);

        $this->assertDatabaseCount('evaluation_criteria', 4);
        $this->assertDatabaseHas('evaluation_criteria', ['name' => 'Saber Conocer', 'order' => 1]);
        $this->assertDatabaseHas('evaluation_criteria', ['name' => 'Saber Hacer', 'order' => 2]);
        $this->assertDatabaseHas('evaluation_criteria', ['name' => 'Saber Ser', 'order' => 3]);
        $this->assertDatabaseHas('evaluation_criteria', ['name' => 'Saber Transferir', 'order' => 4]);
    }

    public function test_performance_levels_are_seeded(): void
    {
        $this->seed(PerformanceLevelSeeder::class);

        $this->assertDatabaseCount('performance_levels', 4);
        $this->assertDatabaseHas('performance_levels', ['name' => 'Insuficiente', 'order' => 1]);
        $this->assertDatabaseHas('performance_levels', ['name' => 'Básico', 'order' => 2]);
        $this->assertDatabaseHas('performance_levels', ['name' => 'Competente', 'order' => 3]);
        $this->assertDatabaseHas('performance_levels', ['name' => 'Destacado', 'order' => 4]);
    }

    public function test_admin_user_is_seeded(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@lasalle.edu.co',
            'name' => 'Administrador',
            'role' => 'admin',
        ]);
    }

    public function test_running_seeder_twice_does_not_duplicate_records(): void
    {
        $this->seed(EvaluationCriterionSeeder::class);
        $this->seed(EvaluationCriterionSeeder::class);

        $this->assertDatabaseCount('evaluation_criteria', 4);
    }

    public function test_full_database_seeder_populates_all_catalogs(): void
    {
        $this->seed();

        $this->assertDatabaseCount('microcurricular_learning_outcome_types', 3);
        $this->assertDatabaseCount('modalities', 3);
        $this->assertDatabaseCount('evaluation_criteria', 4);
        $this->assertDatabaseCount('performance_levels', 4);
        $this->assertDatabaseCount('users', 1);
    }
}
