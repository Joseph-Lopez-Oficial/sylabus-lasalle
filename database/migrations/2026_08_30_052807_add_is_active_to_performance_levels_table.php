<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: add_is_active_to_performance_levels_table
 *
 * Lets a performance level be retired from the grading screen without deleting
 * it, so the grades already assigned to that level keep their meaning and the
 * averages computed from them stay identical.
 *
 * Existing rows default to active, which is the behaviour the system had before
 * this column existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_levels', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_below_basic_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('performance_levels', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
