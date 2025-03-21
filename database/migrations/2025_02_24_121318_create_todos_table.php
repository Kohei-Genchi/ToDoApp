<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("todos", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->string("title");
            $table
                ->enum("location", ["INBOX", "TODAY", "TEMPLATE", "SCHEDULED"])
                ->default("INBOX");
            $table->date("due_date")->nullable();
            $table->time("due_time")->nullable();
            $table
                ->enum("status", [
                    "pending",
                    "ongoing",
                    "paused",
                    "completed",
                    "trashed",
                ])
                ->default("pending");
            $table->string("recurrence_type")->nullable(); // Added here
            $table->date("recurrence_end_date")->nullable(); // Added here
            $table
                ->foreignId("category_id")
                ->nullable()
                ->constrained()
                ->nullOnDelete(); // Added here
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("todos");
    }
};
