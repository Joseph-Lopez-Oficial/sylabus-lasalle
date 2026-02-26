<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_students_table
 *
 * Creates the `students` table. Stores personal and enrollment data for
 * students. Students do NOT have system login credentials; they are managed
 * exclusively by administrators and professors. Students are enrolled into
 * programmings through the `enrollments` table.
 *
 * Relationships:
 *   - Has many: enrollments
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:              Auto-increment primary key.
     * - first_name:      Student's first name(s).
     * - last_name:       Student's last name(s).
     * - document_number: National ID or student ID number.
     * - email:           Student's personal or institutional email.
     * - phone:           Optional contact phone number.
     * - is_active:       Soft-toggle to enable/disable the student record.
     * - timestamps:      Laravel standard created_at / updated_at.
     * - deleted_at:      SoftDelete timestamp. Allows restoring a student record
     *                    while preserving all historical enrollments and grades.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('document_number', 30)->unique();
            $table->string('email', 191)->unique();
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
        Schema::dropIfExists('students');
    }
};
