<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_evaluation_criteria_table
 *
 * Creates the `evaluation_criteria` lookup table. Defines the dimensions
 * used to evaluate student performance. The four standard criteria are:
 *   - Saber Conocer  (Knowledge)
 *   - Saber Hacer    (Skills)
 *   - Saber Ser      (Attitude)
 *   - Saber Transferir (Transfer)
 *
 * Each grade record references one of these criteria to specify which
 * dimension of learning is being evaluated. This is a catalog table
 * seeded once and rarely modified.
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
     * - name:        Criterion name (e.g., "Saber Conocer").
     * - description: Optional explanation of what this criterion evaluates.
     * - order:       Display order in the grading interface.
     * - timestamps:  Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('order')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_criteria');
    }
};
