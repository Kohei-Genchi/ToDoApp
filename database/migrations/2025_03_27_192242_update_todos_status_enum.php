<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Expand the status ENUM to include new kanban statuses
        // For MySQL, we need to modify the enum values
        DB::statement(
            "ALTER TABLE todos MODIFY COLUMN status ENUM('pending', 'ongoing', 'paused', 'completed', 'trashed', 'in_progress', 'review') NOT NULL DEFAULT 'pending'"
        );

        // Update existing status values to match kanban workflow
        // 'ongoing' becomes 'in_progress' for consistency
        DB::statement(
            "UPDATE todos SET status = 'in_progress' WHERE status = 'ongoing'"
        );

        // 'paused' becomes 'review' for consistency
        DB::statement(
            "UPDATE todos SET status = 'review' WHERE status = 'paused'"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert status changes
        DB::statement(
            "UPDATE todos SET status = 'ongoing' WHERE status = 'in_progress'"
        );
        DB::statement(
            "UPDATE todos SET status = 'paused' WHERE status = 'review'"
        );

        // Restore original ENUM values
        DB::statement(
            "ALTER TABLE todos MODIFY COLUMN status ENUM('pending', 'ongoing', 'paused', 'completed', 'trashed') NOT NULL DEFAULT 'pending'"
        );
    }
};
