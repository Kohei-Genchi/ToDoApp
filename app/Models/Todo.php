<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    use HasFactory;

    // 状態定数
    const STATUS_PENDING = "pending";
    const STATUS_COMPLETED = "completed";

    // 場所定数
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
        return $this->recurrence_type !== "none";
    }
}
