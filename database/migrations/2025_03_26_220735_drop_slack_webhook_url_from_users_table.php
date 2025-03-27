<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DropSlackWebhookUrlFromUsersTable extends Migration
{
    public function up()
    {
        // Check if the column exists before trying to drop it
        if (Schema::hasColumn("users", "slack_webhook_url")) {
            Schema::table("users", function (Blueprint $table) {
                $table->dropColumn("slack_webhook_url");
            });
        }
    }

    public function down()
    {
        Schema::table("users", function (Blueprint $table) {
            $table->string("slack_webhook_url")->nullable();
        });
    }
}
