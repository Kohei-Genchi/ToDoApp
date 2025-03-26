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
        // For MySQL, we need to modify the enum values
        DB::statement("ALTER TABLE share_requests MODIFY COLUMN share_type ENUM('task', 'global', 'category') NOT NULL DEFAULT 'task'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE share_requests MODIFY COLUMN share_type ENUM('task', 'global') NOT NULL DEFAULT 'task'");
    }
};