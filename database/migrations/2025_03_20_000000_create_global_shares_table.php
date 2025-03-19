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
        Schema::create("global_shares", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table
                ->foreignId("shared_with_user_id")
                ->constrained("users")
                ->onDelete("cascade");
            $table->enum("permission", ["view", "edit"])->default("view");
            $table->timestamps();

            // Each user can only share globally with another user once
            $table->unique(["user_id", "shared_with_user_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("global_shares");
    }
};
