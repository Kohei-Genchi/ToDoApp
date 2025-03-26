<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = ["name", "color", "user_id"];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    /**
     * Get the users with whom this category is shared
     */
    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, "category_shares")
            ->withPivot("permission")
            ->withTimestamps();
    }

    /**
     * Check if this category is shared with a specific user
     */
    public function isSharedWith(User $user): bool
    {
        return $this->sharedWith->contains($user);
    }

    /**
     * Share this category with a user
     */
    public function shareTo(User $user, string $permission = "view"): void
    {
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
     * Stop sharing this category with a user
     */
    public function unshareFrom(User $user): void
    {
        $this->sharedWith()->detach($user);
    }
}
