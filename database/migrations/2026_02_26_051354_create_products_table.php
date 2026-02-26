<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_products_table
 *
 * Creates the `products` table. A product is the tangible or observable
 * deliverable that a student must produce to demonstrate the achievement
 * of an activity. Products are the lowest level of the academic content
 * hierarchy: Faculty → Program → Nucleus → Competency → Academic Space
 * → Topic → Activity → Product.
 *
 * Relationships:
 *   - Belongs to: activities
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:          Auto-increment primary key.
     * - activity_id: Foreign key referencing the parent activity.
     * - name:        Name or title of the product (e.g., "Lab Report", "Final Project").
     * - description: Optional detailed description or acceptance criteria.
     * - order:       Display order within the activity. Defaults to 1.
     * - is_active:   Soft-toggle to enable/disable without deleting.
     * - timestamps:  Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
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
        Schema::dropIfExists('products');
    }
};
