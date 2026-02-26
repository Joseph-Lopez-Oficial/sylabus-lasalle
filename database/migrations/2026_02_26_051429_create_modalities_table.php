<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_modalities_table
 *
 * Creates the `modalities` lookup table. Defines the delivery mode of an
 * academic programming (e.g., "In-Person", "Virtual", "Hybrid").
 * This is a catalog table seeded once and rarely modified.
 *
 * Relationships:
 *   - Has many: programmings
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:          Auto-increment primary key.
     * - name:        Modality name (e.g., "In-Person", "Virtual", "Hybrid").
     * - description: Optional explanation of this delivery mode.
     * - timestamps:  Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('modalities', function (Blueprint $table) {
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
        Schema::dropIfExists('modalities');
    }
};
