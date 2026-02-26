<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_topics_table
 *
 * Creates the `topics` table. A topic is a thematic unit within an academic
 * space that groups related activities. Topics define the content structure
 * of the course syllabus.
 *
 * Relationships:
 *   - Belongs to: academic_spaces
 *   - Has many:   activities
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:                Auto-increment primary key.
     * - academic_space_id: Foreign key referencing the parent academic space.
     * - name:              Name of the topic (e.g., "Introduction to Algorithms").
     * - order:             Display order within the academic space. Defaults to 1.
     * - description:       Optional extended description or learning goals.
     * - is_active:         Soft-toggle to enable/disable without deleting.
     * - timestamps:        Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_space_id')->constrained('academic_spaces')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('order')->default(1);
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
        Schema::dropIfExists('topics');
    }
};
