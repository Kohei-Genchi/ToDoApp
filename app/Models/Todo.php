<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_PENDING = "pending";
    const STATUS_IN_PROGRESS = "in_progress"; // New for kanban
    const STATUS_REVIEW = "review"; // New for kanban
    const STATUS_COMPLETED = "completed";
    const STATUS_TRASHED = "trashed";

    // Legacy status constants (for backward compatibility)
    const STATUS_ONGOING = "ongoing"; // Maps to in_progress
    const STATUS_PAUSED = "paused"; // Maps to review

    // Location constants
    const LOCATION_INBOX = "INBOX";
    const LOCATION_TODAY = "TODAY";
    const LOCATION_SCHEDULED = "SCHEDULED";
    const LOCATION_TEMPLATE = "TEMPLATE";

    protected $fillable = [
        "title",
        "description", // Added for kanban board
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
     * Check if task is in a kanban-friendly status
     *
     * @return bool
     */
    public function isKanbanStatus(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_REVIEW,
            self::STATUS_COMPLETED,
        ]);
    }

    /**
     * Map legacy status to kanban status
     *
     * @param string $status
     * @return string
     */
    public static function mapLegacyStatus(string $status): string
    {
        switch ($status) {
            case self::STATUS_ONGOING:
                return self::STATUS_IN_PROGRESS;
            case self::STATUS_PAUSED:
                return self::STATUS_REVIEW;
            default:
                return $status;
        }
    }
}
