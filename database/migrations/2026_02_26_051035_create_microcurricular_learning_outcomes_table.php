<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_microcurricular_learning_outcomes_table
 *
 * Creates the `microcurricular_learning_outcomes` table. These are the
 * most granular learning outcomes (resultados de aprendizaje microcurriculares)
 * tied to a specific academic space (course). They are classified by type
 * (Knowledge, Skill, Attitude) and optionally linked to a mesocurricular
 * outcome for vertical curriculum alignment.
 *
 * This table is central to the grading system: professors assign grades
 * per student per microcurricular outcome.
 *
 * Relationships:
 *   - Belongs to: academic_spaces
 *   - Belongs to: microcurricular_learning_outcome_types
 *   - Belongs to (optional): mesocurricular_learning_outcomes
 *   - Has many:   grades
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:                                Auto-increment primary key.
     * - academic_space_id:                 Foreign key to the parent academic space.
     * - type_id:                           Foreign key to the outcome type (Knowledge/Skill/Attitude).
     * - mesocurricular_learning_outcome_id: Optional FK linking this micro-outcome to a
     *                                      mesocurricular outcome for curriculum alignment.
     * - description:                       Full statement of the learning outcome.
     * - is_active:                         Soft-toggle to enable/disable without deleting.
     * - timestamps:                        Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('microcurricular_learning_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_space_id')->constrained('academic_spaces')->cascadeOnDelete();
            $table->foreignId('type_id')->constrained('microcurricular_learning_outcome_types');
            $table->foreignId('mesocurricular_learning_outcome_id')
                ->nullable()
                ->references('id')
                ->on('mesocurricular_learning_outcomes')
                ->nullOnDelete()
                ->index('mlo_meso_outcome_fk');
            $table->text('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('microcurricular_learning_outcomes');
    }
};
