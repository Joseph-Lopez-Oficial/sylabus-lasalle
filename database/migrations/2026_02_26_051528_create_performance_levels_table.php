<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_performance_levels_table
 *
 * Creates the `performance_levels` lookup table. Defines the qualitative
 * scale used to assess each evaluation criterion. The four standard levels
 * are ordered from lowest to highest achievement:
 *   1. Insuficiente  (Insufficient)
 *   2. Básico        (Basic)
 *   3. Competente    (Competent)
 *   4. Destacado     (Outstanding)
 *
 * Each grade record stores which level was assigned for a given criterion.
 * This is a catalog table seeded once and rarely modified.
 *
 * Relationships:
 *   - Has many: grades
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:          Auto-increment primary key.
     * - name:        Level name (e.g., "Destacado").
     * - description: Optional description of what this level represents.
     * - order:       Numeric rank (1 = lowest, 4 = highest). Used for sorting and stats.
     * - timestamps:  Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('performance_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_levels');
    }
};
