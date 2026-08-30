<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_academic_space_analyses_table
 *
 * Creates the `academic_space_analyses` table, which stores the qualitative
 * reading a professor writes about each learning outcome they assessed in a
 * programming. It is the part of the follow-up that grades cannot express: why
 * the group reached that average, how it behaved during the academic space, and
 * what improvements are proposed.
 *
 * The institutional spreadsheet keeps this in its "Analisis del EA" sheet, with
 * one block of three open questions per assessed outcome, so the table is keyed
 * by programming and outcome rather than by programming alone.
 *
 * Relationships:
 *   - Belongs to: programmings
 *   - Belongs to: microcurricular_learning_outcomes
 *   - Belongs to: users (written_by — the professor who wrote the analysis)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:                                  Auto-increment primary key.
     * - programming_id:                      FK to the programming being analysed.
     * - microcurricular_learning_outcome_id: FK to the outcome the analysis refers to.
     * - outcome_performance:                 How the group performed against that outcome.
     * - academic_space_performance:          How the group performed in the academic space.
     * - improvement_proposals:               Analysis and proposed improvements.
     * - written_by:                          FK to the user who last wrote the analysis.
     * - timestamps:                          Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('academic_space_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programming_id')->constrained('programmings')->cascadeOnDelete();
            // The generated constraint name would exceed the 64-character limit
            // MySQL allows for identifiers, so it is named explicitly.
            $table->foreignId('microcurricular_learning_outcome_id');
            $table->foreign('microcurricular_learning_outcome_id', 'analyses_outcome_foreign')
                ->references('id')
                ->on('microcurricular_learning_outcomes')
                ->cascadeOnDelete();
            $table->text('outcome_performance')->nullable();
            $table->text('academic_space_performance')->nullable();
            $table->text('improvement_proposals')->nullable();
            $table->foreignId('written_by')->constrained('users');
            $table->timestamps();

            // One analysis per outcome within a programming: the professor edits
            // the existing text instead of accumulating versions of it.
            $table->unique(
                ['programming_id', 'microcurricular_learning_outcome_id'],
                'analyses_programming_outcome_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_space_analyses');
    }
};
