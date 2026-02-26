<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_programmings_table
 *
 * Creates the `programmings` table. A programming (programación) is the
 * instance of an academic space for a specific period, assigned to a
 * professor with a specific modality. Students are enrolled into
 * programmings (not academic spaces directly), and grades are recorded
 * at the programming level.
 *
 * Example: "Data Structures — 2025-II — Professor Henao — Virtual"
 *
 * Relationships:
 *   - Belongs to: academic_spaces
 *   - Belongs to: professors
 *   - Belongs to: modalities
 *   - Has many:   enrollments
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:                  Auto-increment primary key.
     * - academic_space_id:   Foreign key to the academic space being programmed.
     * - professor_id:        Foreign key to the assigned professor.
     * - modality_id:         Foreign key to the delivery modality.
     * - period:              Academic period identifier (e.g., "2025-I", "2025-II").
     * - group:               Optional group/section identifier (e.g., "A", "B", "01").
     * - is_active:           Soft-toggle to enable/disable this programming.
     * - timestamps:          Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('programmings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_space_id')->constrained('academic_spaces')->cascadeOnDelete();
            $table->foreignId('professor_id')->constrained('professors');
            $table->foreignId('modality_id')->constrained('modalities');
            $table->string('period', 20);
            $table->string('group', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academic_space_id', 'professor_id', 'period', 'group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programmings');
    }
};
