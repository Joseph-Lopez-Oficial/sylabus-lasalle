<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('professors_user_id_unique');
            $table->foreignId('user_id')->nullable()->change();
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('professors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('professors_user_id_unique');
            $table->foreignId('user_id')->nullable(false)->change();
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
