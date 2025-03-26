<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryShare extends Model
{
    use HasFactory;

    protected $fillable = ["category_id", "user_id", "permission"];

    /**
     * Get the category being shared
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user with whom the category is shared
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
