<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_faculties_table
 *
 * Creates the `faculties` table, which represents the top-level academic
 * organizational unit within the university. Each faculty groups one or
 * more academic programs under a common administrative structure.
 *
 * Relationships:
 *   - Has many: programs
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:          Auto-increment primary key.
     * - name:        Full name of the faculty (e.g., "Faculty of Engineering").
     * - code:        Short unique identifier (e.g., "ENG"). Max 20 chars.
     * - description: Optional detailed description of the faculty.
     * - is_active:   Soft-toggle to enable/disable the faculty without deleting it.
     * - timestamps:  Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
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
        Schema::dropIfExists('faculties');
    }
};
