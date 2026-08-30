<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: add_grade_value_to_performance_levels_table
 *
 * Adds the institutional grade of each performance level to the table, so the
 * scale stops living in a constant inside the model.
 *
 * The column is nullable because a level created from the administration UI may
 * not have a value assigned yet; the application treats a null value as "does
 * not contribute to the average" rather than as zero.
 *
 * Existing rows are backfilled with the values the code used until now
 * (1.3 / 2.5 / 3.8 / 5.0 for orders 1 to 4), so statistics computed before and
 * after this migration produce identical results.
 */
return new class extends Migration
{
    /**
     * Institutional grade previously hardcoded for each level order.
     */
    private const LEGACY_SCALE = [
        1 => 1.3,
        2 => 2.5,
        3 => 3.8,
        4 => 5.0,
    ];

    public function up(): void
    {
        Schema::table('performance_levels', function (Blueprint $table) {
            $table->decimal('grade_value', 3, 2)->nullable()->after('order');
            $table->boolean('is_below_basic_threshold')->default(false)->after('grade_value');
        });

        foreach (self::LEGACY_SCALE as $order => $value) {
            DB::table('performance_levels')
                ->where('order', $order)
                ->update(['grade_value' => $value]);
        }

        // Order 2 is the "Básico" level: the threshold under which a student is
        // reported as being at risk.
        DB::table('performance_levels')
            ->where('order', 2)
            ->update(['is_below_basic_threshold' => true]);
    }

    public function down(): void
    {
        Schema::table('performance_levels', function (Blueprint $table) {
            $table->dropColumn(['grade_value', 'is_below_basic_threshold']);
        });
    }
};
