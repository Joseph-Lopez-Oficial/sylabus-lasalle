<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_activities_table
 *
 * Creates the `activities` table. Activities are specific pedagogical tasks
 * assigned within a topic. Each activity has a type (e.g., lab practice,
 * team work) and produces one or more deliverables (products).
 *
 * Relationships:
 *   - Belongs to: topics
 *   - Belongs to: activity_types
 *   - Has many:   products
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:               Auto-increment primary key.
     * - topic_id:         Foreign key referencing the parent topic.
     * - activity_type_id: Foreign key referencing the activity type.
     * - name:             Name or title of the activity.
     * - description:      Optional detailed description or instructions.
     * - order:            Display order within the topic. Defaults to 1.
     * - is_active:        Soft-toggle to enable/disable without deleting.
     * - timestamps:       Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->foreignId('activity_type_id')->constrained('activity_types');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
