<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop slack_webhook_url column from users table
        Schema::table("users", function (Blueprint $table) {
            $table->dropColumn("slack_webhook_url");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add slack_webhook_url column back if migration is rolled back
        Schema::table("users", function (Blueprint $table) {
            $table->string("slack_webhook_url")->nullable();
        });
    }
};
