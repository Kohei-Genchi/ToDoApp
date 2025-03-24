<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Billable;

    protected $fillable = [
        "name",
        "email",
        "password",
        "google_id",
        "avatar",
        "stripe_id",
        "subscription_id",
        "morning_reminder_time",
        "evening_reminder_time",
        "slack_webhook_url",
        "line_notify_token",
    ];

    protected $hidden = ["password", "remember_token"];

    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "morning_reminder_time" => "datetime",
            "evening_reminder_time" => "datetime",
        ];
    }

    /**
     * Get all todos for the user.
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    /**
     * Get all categories for the user.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get tasks shared with this user.
     */
    public function sharedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Todo::class, "task_shares")
            ->withPivot("permission")
            ->withTimestamps();
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail()
    {
        return $this->email;
    }

    /**
     * Route notifications for the Slack channel.
     */
    public function routeNotificationForSlack()
    {
        if (!empty($this->slack_webhook_url)) {
            Log::info("Routing notification to Slack webhook", [
                "user_id" => $this->id,
                "webhook" => substr($this->slack_webhook_url, 0, 15) . "...",
            ]);
            return $this->slack_webhook_url;
        }

        Log::warning("No Slack webhook URL configured for user {$this->id}");
        return null;
    }

    public function routeNotificationForLine()
    {
        return $this->line_notify_token;
    }

    public function globallySharedWith()
    {
        return $this->hasMany(GlobalShare::class, "user_id");
    }

    /**
     * Get the users who have shared all tasks globally with this user.
     */
    public function globallySharedBy()
    {
        return $this->hasMany(GlobalShare::class, "shared_with_user_id");
    }
}
