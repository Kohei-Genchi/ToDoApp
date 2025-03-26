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
        Schema::create("share_requests", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("user_id")
                ->comment("Requester user ID")
                ->constrained()
                ->onDelete("cascade");
            $table
                ->foreignId("todo_id")
                ->nullable()
                ->constrained()
                ->onDelete("cascade");
            $table->foreignId("category_id")->nullable();
            $table->string("recipient_email");
            $table->string("token")->unique();
            $table->enum("share_type", ["task", "global"])->default("task");
            $table->enum("permission", ["view", "edit"])->default("view");
            $table
                ->enum("status", ["pending", "approved", "rejected"])
                ->default("pending");
            $table->timestamp("expires_at");
            $table->timestamp("responded_at")->nullable();
            $table->timestamps();

            // Composite index for faster lookups by user and status
            $table->index(["user_id", "status"]);
            // Ensure token is searchable for verification
            $table->index("token");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("share_requests");
    }
};
