<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_programs_table
 *
 * Creates the `programs` table, which represents academic programs
 * (undergraduate/graduate degrees) offered by a faculty. Programs are
 * the second level in the academic hierarchy.
 *
 * Relationships:
 *   - Belongs to: faculties
 *   - Has many:   problematic_nuclei
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:         Auto-increment primary key.
     * - faculty_id: Foreign key referencing the parent faculty.
     * - name:       Full name of the program (e.g., "Software Engineering").
     * - code:       Short unique identifier (e.g., "ISW"). Max 20 chars.
     * - description: Optional detailed description of the program.
     * - is_active:  Soft-toggle to enable/disable the program without deleting it.
     * - timestamps: Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculties')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->unique();
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
        Schema::dropIfExists('programs');
    }
};
