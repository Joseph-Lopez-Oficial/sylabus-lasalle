<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_professors_table
 *
 * Creates the `professors` table. Stores personal and institutional data
 * for professors. A professor is linked to a `users` record so they can
 * log in to the system. Professors can be assigned to one or more
 * programmings (course sections).
 *
 * Relationships:
 *   - Belongs to: users (one-to-one via user_id)
 *   - Has many:   programmings
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:                  Auto-increment primary key.
     * - user_id:             Foreign key referencing the linked system user account.
     * - first_name:          Professor's first name(s).
     * - last_name:           Professor's last name(s).
     * - document_number:     National ID or institutional document number.
     * - institutional_email: Official university email address.
     * - phone:               Optional contact phone number.
     * - is_active:           Soft-toggle to enable/disable the professor account.
     * - timestamps:          Laravel standard created_at / updated_at.
     * - deleted_at:          SoftDelete timestamp. Allows restoring a professor record
     *                        while preserving all historical grades and programmings.
     */
    public function up(): void
    {
        Schema::create('professors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('document_number', 30)->unique();
            $table->string('institutional_email', 191)->unique();
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professors');
    }
};
