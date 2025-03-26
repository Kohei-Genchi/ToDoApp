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
        // Drop tables for deprecated sharing methods
        Schema::dropIfExists("task_shares");
        Schema::dropIfExists("global_shares");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-create tables if needed in case of rollback
        Schema::create("task_shares", function (Blueprint $table) {
            $table->id();
            $table->foreignId("todo_id")->constrained()->onDelete("cascade");
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->enum("permission", ["view", "edit"])->default("view");
            $table->timestamps();
            $table->unique(["todo_id", "user_id"]);
        });

        Schema::create("global_shares", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table
                ->foreignId("shared_with_user_id")
                ->constrained("users")
                ->onDelete("cascade");
            $table->enum("permission", ["view", "edit"])->default("view");
            $table->timestamps();
            $table->unique(["user_id", "shared_with_user_id"]);
        });
    }
};
