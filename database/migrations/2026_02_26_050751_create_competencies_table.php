<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_competencies_table
 *
 * Creates the `competencies` table. A competency defines a specific set of
 * knowledge, skills, and attitudes that students must develop within a
 * problematic nucleus. Competencies are the bridge between the macro
 * curriculum and the academic spaces (courses).
 *
 * Relationships:
 *   - Belongs to: problematic_nuclei
 *   - Has many:   mesocurricular_learning_outcomes
 *   - Has many:   academic_spaces
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:                   Auto-increment primary key.
     * - problematic_nucleus_id: Foreign key referencing the parent nucleus.
     * - name:                 Short descriptive name of the competency.
     * - description:          Full statement of the competency.
     * - is_active:            Soft-toggle to enable/disable without deleting.
     * - timestamps:           Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('problematic_nucleus_id')->constrained('problematic_nuclei')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competencies');
    }
};
