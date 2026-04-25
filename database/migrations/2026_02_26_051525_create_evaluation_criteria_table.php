<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_evaluation_criteria_table
 *
 * Creates the `evaluation_criteria` lookup table. Each criterion belongs to
 * a microcurricular_learning_outcome_type (Conocimiento, Habilidad, Actitud).
 * The criteria per type are:
 *   Conocimiento: Comprensión Conceptual, Aplicación de Conocimientos, Análisis, Dominio del Vocabulario Específico
 *   Habilidad:    Dominio del Procedimiento, Adaptabilidad, Eficacia en la Ejecución
 *   Actitud:      Compromiso y Responsabilidad, Colaboración y Trabajo en Equipo, Respeto
 *
 * Relationships:
 *   - Belongs to: microcurricular_learning_outcome_types
 *   - Has many:   grades
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:                                     Auto-increment primary key.
     * - microcurricular_learning_outcome_type_id: FK to the outcome type this criterion belongs to.
     * - name:                                   Criterion name (unique per type).
     * - description:                            Optional explanation.
     * - order:                                  Display order within the type.
     * - timestamps:                             Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('microcurricular_learning_outcome_type_id');
            $table->foreign('microcurricular_learning_outcome_type_id', 'ec_outcome_type_fk')
                ->references('id')
                ->on('microcurricular_learning_outcome_types')
                ->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('order')->default(1);
            $table->timestamps();

            $table->unique(['microcurricular_learning_outcome_type_id', 'name'], 'ec_type_name_unique');
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
