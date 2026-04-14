<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competencies', function (Blueprint $table) {
            $table->text('name')->change();
        });

        Schema::table('problematic_nuclei', function (Blueprint $table) {
            $table->text('name')->change();
        });
    }

    public function down(): void
    {
        Schema::table('competencies', function (Blueprint $table) {
            $table->string('name')->change();
        });

        Schema::table('problematic_nuclei', function (Blueprint $table) {
            $table->string('name')->change();
        });
    }
};
