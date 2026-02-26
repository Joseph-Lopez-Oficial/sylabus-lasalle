<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_problematic_nuclei_table
 *
 * Creates the `problematic_nuclei` table. A problematic nucleus (núcleo
 * problemático) is a thematic axis that groups related competencies within
 * an academic program. It represents a central problem or challenge the
 * curriculum addresses at a macro level.
 *
 * Relationships:
 *   - Belongs to: programs
 *   - Has many:   competencies
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:          Auto-increment primary key.
     * - program_id:  Foreign key referencing the parent program.
     * - name:        Name of the problematic nucleus.
     * - description: Optional extended description or guiding question.
     * - is_active:   Soft-toggle to enable/disable without deleting.
     * - timestamps:  Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('problematic_nuclei', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
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
        Schema::dropIfExists('problematic_nuclei');
    }
};
