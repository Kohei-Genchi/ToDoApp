<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShareRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "todo_id",
        "category_id",
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
     * Get the category related to this share request
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
            Log::error("Share request is not valid", ["id" => $this->id]);
            return false;
        }

        $this->status = "approved";
        $this->responded_at = now();
        $this->save();

        // Process category sharing (the only supported type now)
        if ($this->share_type === "category" && $this->category) {
            return $this->processCategorySharing();
        }

        Log::error("Share request type not supported", [
            "share_type" => $this->share_type,
        ]);
        return false;
    }

    protected function processCategorySharing(): bool
    {
        try {
            // Find the recipient user
            $recipientUser = User::where(
                "email",
                $this->recipient_email
            )->first();

            if (!$recipientUser) {
                Log::error("Recipient user not found", [
                    "email" => $this->recipient_email,
                ]);
                return false;
            }

            // Check if category exists and is loaded
            if (!$this->category) {
                $this->load("category");
                if (!$this->category) {
                    Log::error("Category not found or not loaded", [
                        "category_id" => $this->category_id,
                    ]);
                    return false;
                }
            }

            // Try direct DB insert as a fallback
            try {
                // First check if the entry already exists
                $exists = DB::table("category_shares")
                    ->where("category_id", $this->category_id)
                    ->where("user_id", $recipientUser->id)
                    ->exists();

                if ($exists) {
                    return true;
                }

                // Insert directly via DB query
                $inserted = DB::table("category_shares")->insert([
                    "category_id" => $this->category_id,
                    "user_id" => $recipientUser->id,
                    "permission" => $this->permission,
                    "created_at" => now(),
                    "updated_at" => now(),
                ]);

                return $inserted;
            } catch (\Exception $e) {
                Log::error("Error in direct DB insert: " . $e->getMessage());
            }

            // Original approach using the model relationship
            try {
                // Use transaction for safety
                DB::beginTransaction();
                try {
                    $this->category->shareTo($recipientUser, $this->permission);
                    DB::commit();
                    return true;
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Error in transaction: " . $e->getMessage());
                    return false;
                }
            } catch (\Exception $e) {
                Log::error(
                    "Error processing category sharing: " . $e->getMessage()
                );
                return false;
            }
        } catch (\Exception $e) {
            Log::error(
                "Error processing category sharing: " . $e->getMessage()
            );
            return false;
        }
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
