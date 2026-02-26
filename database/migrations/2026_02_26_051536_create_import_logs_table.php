<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_import_logs_table
 *
 * Creates the `import_logs` table. Tracks every Excel import operation
 * performed by a professor or admin. Stores the result of each import:
 * how many rows were processed, how many succeeded, and how many failed,
 * along with the error details for failed rows.
 *
 * This table provides an audit trail and allows professors to review
 * the outcome of bulk grade imports without losing error details.
 *
 * Relationships:
 *   - Belongs to: users (imported_by)
 *   - Belongs to: programmings
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columns:
     * - id:              Auto-increment primary key.
     * - imported_by:     FK to the user who performed the import.
     * - programming_id:  FK to the programming this import was for.
     * - file_name:       Original file name of the uploaded Excel file.
     * - total_rows:      Total number of data rows found in the file.
     * - successful_rows: Number of rows that were imported without errors.
     * - failed_rows:     Number of rows that failed to import.
     * - errors:          JSON array with details of each failed row and its error message.
     * - status:          Overall import status: 'pending', 'processing', 'completed', 'failed'.
     * - imported_at:     Timestamp when the import was completed.
     * - timestamps:      Laravel standard created_at / updated_at.
     */
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_by')->constrained('users');
            $table->foreignId('programming_id')->constrained('programmings')->cascadeOnDelete();
            $table->string('file_name');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('errors')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
