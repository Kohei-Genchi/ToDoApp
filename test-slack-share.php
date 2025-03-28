<?php
require_once __DIR__ . "/vendor/autoload.php";

use Illuminate\Foundation\Application;
use App\Models\ShareRequest;
use App\Models\User;
use App\Notifications\ShareNotification;

$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the latest share request
$shareRequest = ShareRequest::where("status", "pending")->latest()->first();

if (!$shareRequest) {
    echo "No pending share requests found.\n";
    exit();
}

// Get the recipient user
$recipientUser = User::where("email", $shareRequest->recipient_email)->first();

if (!$recipientUser) {
    echo "Recipient user not found: {$shareRequest->recipient_email}\n";
    exit();
}

// Check webhook URL
echo "Slack webhook URL: " .
    (empty($recipientUser->slack_webhook_url) ? "NOT SET" : "SET") .
    "\n";

// Get the category name
$category = \App\Models\Category::find($shareRequest->category_id);
$categoryName = $category ? $category->name : "Unknown Category";

// Get the requester name
$requester = User::find($shareRequest->user_id);
$requesterName = $requester ? $requester->name : "Unknown User";

// Manually send notification
try {
    echo "Attempting to send notification...\n";
    $recipientUser->notify(
        new ShareNotification(
            $shareRequest,
            $requesterName,
            $categoryName,
            "カテゴリー"
        )
    );
    echo "Notification sent successfully!\n";
} catch (\Exception $e) {
    echo "Error sending notification: " . $e->getMessage() . "\n";
}
