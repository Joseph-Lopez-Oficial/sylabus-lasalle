<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_enrollments_table
 *
 * Creates the `enrollments` table. Records the enrollment of a student
 * in a specific programming (course section). This is the pivot between
 * students and programmings. Grades are associated with enrollments.
 *
 * A student can be enrolled in multiple programmings, but only once per
 * programming (enforced by a unique constraint).
 *
 * Relationships:
 *   - Belongs to: students
 *   - Belongs to: programmings
 *   - Has many:   grades
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:              Auto-increment primary key.
     * - student_id:      Foreign key referencing the enrolled student.
     * - programming_id:  Foreign key referencing the programming (course section).
     * - enrolled_at:     Date when the student was enrolled. Defaults to now.
     * - is_active:       Whether this enrollment is currently active.
     * - timestamps:      Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('programming_id')->constrained('programmings')->cascadeOnDelete();
            $table->date('enrolled_at')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['student_id', 'programming_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
