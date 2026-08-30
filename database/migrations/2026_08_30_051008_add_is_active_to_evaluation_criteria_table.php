<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: add_is_active_to_evaluation_criteria_table
 *
 * Adds the activation flag every other catalogue in the system already has, so
 * a criterion that stops being used can be retired without deleting it and
 * losing the grades recorded against it.
 *
 * Existing rows default to active, so the behaviour of current programmings
 * does not change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('order');
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
