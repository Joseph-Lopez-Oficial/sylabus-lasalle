<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_activity_types_table
 *
 * Creates the `activity_types` lookup table. Classifies activities
 * within the academic space plan (e.g., "Individual Work", "Team Work",
 * "Lab Practice", "Project"). This is a catalog table seeded once.
 *
 * Relationships:
 *   - Has many: activities
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:          Auto-increment primary key.
     * - name:        Type name (e.g., "Lab Practice", "Team Work").
     * - description: Optional explanation of when this type applies.
     * - timestamps:  Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('activity_types', function (Blueprint $table) {
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
        Schema::dropIfExists('activity_types');
    }
};
