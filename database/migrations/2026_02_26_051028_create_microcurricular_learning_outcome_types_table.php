<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_microcurricular_learning_outcome_types_table
 *
 * Creates the `microcurricular_learning_outcome_types` lookup table.
 * This table stores the classification types for microcurricular learning
 * outcomes. The three standard types are:
 *   - Knowledge  (Saber Conocer)
 *   - Skill      (Saber Hacer)
 *   - Attitude   (Saber Ser)
 *
 * This is a catalog table seeded once and rarely modified.
 *
 * Relationships:
 *   - Has many: microcurricular_learning_outcomes
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:          Auto-increment primary key.
     * - name:        Type name (e.g., "Knowledge", "Skill", "Attitude").
     * - description: Optional description of what this type represents.
     * - timestamps:  Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('microcurricular_learning_outcome_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('microcurricular_learning_outcome_types');
    }
};
