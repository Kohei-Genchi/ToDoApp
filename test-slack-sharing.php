<?php

// Save this file as test-slack-sharing.php in your project root
// Run with: php test-slack-sharing.php SLACK_WEBHOOK_URL

if ($argc < 2) {
    echo "Usage: php test-slack-sharing.php SLACK_WEBHOOK_URL\n";
    exit(1);
}

$webhookUrl = $argv[1];

// Bootstrap Laravel
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\ShareRequest;
use App\Notifications\ShareNotification;
use Illuminate\Support\Facades\URL;

echo "Testing Slack category sharing functionality...\n";

// Create or update test users
$owner = User::firstOrCreate(
    ["email" => "test-owner@example.com"],
    [
        "name" => "Test Owner",
        "password" => bcrypt("password123"),
    ]
);

$recipient = User::firstOrCreate(
    ["email" => "test-recipient@example.com"],
    [
        "name" => "Test Recipient",
        "password" => bcrypt("password123"),
        "slack_webhook_url" => $webhookUrl,
    ]
);

// Update webhook URL if needed
if ($recipient->slack_webhook_url !== $webhookUrl) {
    $recipient->slack_webhook_url = $webhookUrl;
    $recipient->save();
    echo "Updated recipient's Slack webhook URL.\n";
}

// Create a test category
$category = Category::firstOrCreate(
    ["name" => "Test Category", "user_id" => $owner->id],
    ["color" => "#ff5722"]
);

echo "Created test category: {$category->name}\n";

// Create a share request
$token = ShareRequest::generateToken();
$shareRequest = new ShareRequest([
    "user_id" => $owner->id,
    "category_id" => $category->id,
    "recipient_email" => $recipient->email,
    "token" => $token,
    "share_type" => "category",
    "permission" => "view",
    "status" => "pending",
    "expires_at" => now()->addDays(7),
]);

$shareRequest->save();

echo "Created share request with token: {$token}\n";

// Generate approval/rejection URLs for reference
$approveUrl = URL::signedRoute("share-requests.web.approve", [
    "token" => $token,
]);
$rejectUrl = URL::signedRoute("share-requests.web.reject", ["token" => $token]);

echo "Approval URL: {$approveUrl}\n";
echo "Rejection URL: {$rejectUrl}\n";

// Send the notification
try {
    $notification = new ShareNotification(
        $shareRequest,
        $owner->name,
        $category->name,
        "カテゴリー"
    );

    $recipient->notify($notification);

    echo "Slack notification sent successfully!\n";
    echo "Check your Slack application for the share request notification.\n";
    echo "You should see buttons to approve or reject the share request.\n";
} catch (\Exception $e) {
    echo "Error sending notification: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Test completed.\n";
