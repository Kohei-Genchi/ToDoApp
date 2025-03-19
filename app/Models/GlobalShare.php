<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalShare extends Model
{
    use HasFactory;

    protected $fillable = ["user_id", "shared_with_user_id", "permission"];

    /**
     * Get the user who shared their tasks.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    /**
     * Get the user with whom tasks are shared.
     */
    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(User::class, "shared_with_user_id");
    }
}
