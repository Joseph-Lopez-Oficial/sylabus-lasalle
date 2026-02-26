<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_academic_spaces_table
 *
 * Creates the `academic_spaces` table. An academic space (espacio académico)
 * is the equivalent of a course or subject within a program. It belongs to a
 * competency and contains the detailed microcurricular design: learning
 * outcomes, topics, activities, and products.
 *
 * Relationships:
 *   - Belongs to: competencies
 *   - Has many:   microcurricular_learning_outcomes
 *   - Has many:   topics
 *   - Has many:   programmings
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:             Auto-increment primary key.
     * - competency_id:  Foreign key referencing the parent competency.
     * - name:           Name of the academic space (e.g., "Data Structures").
     * - code:           Unique short identifier (e.g., "EST-DAT"). Max 20 chars.
     * - credits:        Number of academic credits assigned to this space.
     * - semester:       Semester number in which this space is typically taught.
     * - description:    Optional extended description or syllabus summary.
     * - is_active:      Soft-toggle to enable/disable without deleting.
     * - timestamps:     Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('academic_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_id')->constrained('competencies')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->unsignedTinyInteger('credits')->default(3);
            $table->unsignedTinyInteger('semester')->nullable();
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
        Schema::dropIfExists('academic_spaces');
    }
};
