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
        Schema::create("task_shares", function (Blueprint $table) {
            $table->id();
            $table->foreignId("todo_id")->constrained()->onDelete("cascade");
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->enum("permission", ["view", "edit"])->default("view");
            $table->timestamps();

            // Ensure each task can only be shared once with each user
            $table->unique(["todo_id", "user_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("task_shares");
    }
};
