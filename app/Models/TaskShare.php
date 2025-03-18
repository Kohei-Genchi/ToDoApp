<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskShare extends Model
{
    protected $fillable = ["todo_id", "user_id", "permission"];

    /**
     * Get the todo that is shared.
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /**
     * Get the user with whom the todo is shared.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
