<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Illuminate\Support\Facades\Log;

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
     * Get categories shared with this user.
     */
    public function sharedCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, "category_shares")
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
     * Route notifications for the Line channel.
     */
    public function routeNotificationForLine()
    {
        return $this->line_notify_token;
    }
}
