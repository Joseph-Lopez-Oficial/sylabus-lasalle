<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_mesocurricular_learning_outcomes_table
 *
 * Creates the `mesocurricular_learning_outcomes` table. These are
 * intermediate-level learning outcomes (resultados mesocurriculares)
 * that belong to a competency and serve as observable milestones at the
 * program level. Microcurricular outcomes from specific courses reference
 * these to establish vertical alignment across the curriculum.
 *
 * Relationships:
 *   - Belongs to: competencies
 *   - Has many:   microcurricular_learning_outcomes (via FK on that table)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:             Auto-increment primary key.
     * - competency_id:  Foreign key referencing the parent competency.
     * - description:    Full statement of the mesocurricular learning outcome.
     * - is_active:      Soft-toggle to enable/disable without deleting.
     * - timestamps:     Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('mesocurricular_learning_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_id')->constrained('competencies')->cascadeOnDelete();
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
        Schema::dropIfExists('mesocurricular_learning_outcomes');
    }
};
