<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Todo extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_PENDING = "pending";
    const STATUS_ONGOING = "ongoing";
    const STATUS_PAUSED = "paused";
    const STATUS_COMPLETED = "completed";
    const STATUS_TRASHED = "trashed";

    // Location constants
    const LOCATION_INBOX = "INBOX";
    const LOCATION_TODAY = "TODAY";
    const LOCATION_SCHEDULED = "SCHEDULED";
    const LOCATION_TEMPLATE = "TEMPLATE";

    protected $fillable = [
        "title",
        "due_date",
        "due_time",
        "status",
        "location",
        "user_id",
        "category_id",
        "recurrence_type",
        "recurrence_end_date",
    ];

    protected $casts = [
        "due_date" => "date",
        "due_time" => "datetime",
        "recurrence_end_date" => "date",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isRecurring(): bool
    {
        return $this->recurrence_type !== "none" &&
            $this->recurrence_type !== null;
    }

    /**
     * Get the users with whom this task is shared.
     */
    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, "task_shares")
            ->withPivot("permission")
            ->withTimestamps();
    }

    /**
     * Check if this task is shared with the specified user.
     */
    public function isSharedWith(User $user): bool
    {
        return $this->sharedWith->contains($user);
    }

    /**
     * Share this task with a user.
     */
    public function shareTo(User $user, string $permission = "view"): void
    {
        // Check if we've reached the maximum number of shares
        if ($this->sharedWith->count() >= 5) {
            throw new \Exception(
                "Maximum share limit (5) reached for this task."
            );
        }

        // Check if it's already shared with this user
        if (!$this->isSharedWith($user)) {
            $this->sharedWith()->attach($user, ["permission" => $permission]);
        } else {
            // Update the permission if already shared
            $this->sharedWith()->updateExistingPivot($user->id, [
                "permission" => $permission,
            ]);
        }
    }

    /**
     * Stop sharing this task with a user.
     */
    public function unshareFrom(User $user): void
    {
        $this->sharedWith()->detach($user);
    }
}
