<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        "abatar",
        "stripe_id",
        "subscription_id",
        "morning_reminder_time",
        "evening_reminder_time",
        "slack_webhook_url", // Add this for custom Slack webhook
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
        return $this->slack_webhook_url ??
            config("services.slack.notifications.bot_user_oauth_token");
    }
}
