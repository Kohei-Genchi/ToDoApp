<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ShareRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "todo_id",
        "recipient_email",
        "token",
        "share_type",
        "permission",
        "status",
        "expires_at",
        "responded_at",
    ];

    protected $casts = [
        "expires_at" => "datetime",
        "responded_at" => "datetime",
    ];

    /**
     * Get the user who initiated the share request
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    /**
     * Get the task related to this share request
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /**
     * Check if the request is still valid and pending
     */
    public function isValid(): bool
    {
        return $this->status === "pending" && now()->lt($this->expires_at);
    }

    /**
     * Mark the request as approved
     */
    public function approve(): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        $this->status = "approved";
        $this->responded_at = now();
        $this->save();

        // Process the actual sharing
        if ($this->share_type === "task" && $this->todo) {
            return $this->processTaskSharing();
        } elseif ($this->share_type === "global") {
            return $this->processGlobalSharing();
        }

        return false;
    }

    /**
     * Mark the request as rejected
     */
    public function reject(): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        $this->status = "rejected";
        $this->responded_at = now();

        return $this->save();
    }

    /**
     * Process the task sharing after approval
     */
    protected function processTaskSharing(): bool
    {
        try {
            // Find the recipient user
            $recipientUser = User::where(
                "email",
                $this->recipient_email
            )->first();

            if (!$recipientUser) {
                return false;
            }

            // Share the task with the user
            $this->todo->shareTo($recipientUser, $this->permission);

            return true;
        } catch (\Exception $e) {
            \Log::error("Error processing task sharing: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process the global sharing after approval
     */
    protected function processGlobalSharing(): bool
    {
        try {
            // Find the recipient user
            $recipientUser = User::where(
                "email",
                $this->recipient_email
            )->first();

            if (!$recipientUser) {
                return false;
            }

            // Create a global share
            GlobalShare::create([
                "user_id" => $this->user_id,
                "shared_with_user_id" => $recipientUser->id,
                "permission" => $this->permission,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error("Error processing global sharing: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a new unique token for share request
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where("token", $token)->exists());

        return $token;
    }
}
