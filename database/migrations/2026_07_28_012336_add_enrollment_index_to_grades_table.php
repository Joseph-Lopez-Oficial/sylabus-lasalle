<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: add_enrollment_index_to_grades_table
 *
 * Adds a standalone index on `grades.enrollment_id`.
 *
 * The table already has a composite unique index led by `enrollment_id`, but a
 * narrower dedicated index is cheaper to scan for the very frequent
 * `whereIn('enrollment_id', ...)` lookups that drive StatisticsService, the
 * professor dashboard and the completeness check, none of which constrain the
 * outcome or criterion columns.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->index('enrollment_id', 'grades_enrollment_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex('grades_enrollment_id_index');
        });
    }
};
