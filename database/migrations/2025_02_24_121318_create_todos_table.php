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
            $table->text("description")->nullable();
            $table
                ->enum("location", ["INBOX", "TODAY", "TEMPLATE", "SCHEDULED"])
                ->default("INBOX");
            $table->date("due_date")->nullable();
            $table->time("due_time")->nullable();
            // ここを変更: 古いステータス値を削除し、新しいステータス値を直接使用
            $table
                ->enum("status", [
                    "pending",
                    "in_progress", // "ongoing"ではなく
                    "review", // "paused"ではなく
                    "completed",
                    "trashed",
                ])
                ->default("pending");
            $table->string("recurrence_type")->nullable();
            $table->date("recurrence_end_date")->nullable();
            $table
                ->foreignId("category_id")
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("todos");
    }
};
