<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_grades_table
 *
 * Creates the `grades` table. This is the core transactional table of the
 * system. It records the performance level assigned to a student for a
 * specific microcurricular learning outcome and evaluation criterion,
 * within a given enrollment (course section).
 *
 * A complete grade record answers:
 *   "Student X, enrolled in programming Y, achieved level Z
 *    on criterion C for microcurricular outcome O."
 *
 * The unique constraint ensures that a student can only have one grade
 * per (enrollment, outcome, criterion) combination. A professor can
 * update a grade by overwriting this record.
 *
 * Relationships:
 *   - Belongs to: enrollments
 *   - Belongs to: microcurricular_learning_outcomes
 *   - Belongs to: evaluation_criteria
 *   - Belongs to: performance_levels
 *   - Belongs to: users (graded_by — the professor who assigned the grade)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:                                  Auto-increment primary key.
     * - enrollment_id:                       FK to the student's enrollment in the programming.
     * - microcurricular_learning_outcome_id: FK to the specific outcome being graded.
     * - evaluation_criterion_id:             FK to the criterion being evaluated.
     * - performance_level_id:                FK to the performance level assigned.
     * - graded_by:                           FK to the user (professor) who assigned the grade.
     * - observations:                        Optional qualitative feedback from the professor.
     * - graded_at:                           Timestamp when the grade was last assigned.
     * - timestamps:                          Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('microcurricular_learning_outcome_id')
                ->constrained('microcurricular_learning_outcomes')
                ->cascadeOnDelete();
            $table->foreignId('evaluation_criterion_id')->constrained('evaluation_criteria');
            $table->foreignId('performance_level_id')->constrained('performance_levels');
            $table->foreignId('graded_by')->constrained('users');
            $table->text('observations')->nullable();
            $table->timestamp('graded_at')->useCurrent();
            $table->timestamps();

            $table->unique(
                ['enrollment_id', 'microcurricular_learning_outcome_id', 'evaluation_criterion_id'],
                'grades_enrollment_outcome_criterion_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
