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
        Schema::create("category_shares", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("category_id")
                ->constrained()
                ->onDelete("cascade");
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->string("permission")->default("view"); // 'view' or 'edit'
            $table->timestamps();

            // Each category can only be shared once with a specific user
            $table->unique(["category_id", "user_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("category_shares");
    }
};
