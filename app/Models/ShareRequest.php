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
        "category_id",
        "recipient_email",
        "token",
        "share_type", // Now supports only 'category'
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
        Log::info("ShareRequest::approve called", ["id" => $this->id]);

        if (!$this->isValid()) {
            Log::error("Share request is not valid", ["id" => $this->id]);
            return false;
        }

        $this->status = "approved";
        $this->responded_at = now();
        $this->save();

        Log::info("Share request marked as approved", ["id" => $this->id]);

        // Process category sharing (the only supported type now)
        if ($this->share_type === "category" && $this->category) {
            Log::info("Processing category sharing", [
                "category_id" => $this->category_id,
                "share_type" => $this->share_type,
            ]);
            return $this->processCategorySharing();
        }

        Log::error("Share request type not supported", [
            "share_type" => $this->share_type,
            "has_category" => $this->category ? true : false,
        ]);
        return false;
    }

    protected function processCategorySharing(): bool
    {
        try {
            Log::info("processCategorySharing start", [
                "category_id" => $this->category_id,
                "recipient_email" => $this->recipient_email,
            ]);

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

            Log::info("Recipient user found", [
                "id" => $recipientUser->id,
                "email" => $recipientUser->email,
            ]);

            // Check if category exists and is loaded
            if (!$this->category) {
                Log::error("Category not found or not loaded", [
                    "category_id" => $this->category_id,
                ]);

                // 追加: カテゴリーを明示的に再読み込み
                $this->load("category");

                // 再チェック
                if (!$this->category) {
                    Log::error(
                        "Category still not loaded after explicit loading"
                    );
                    return false;
                }

                Log::info("Category loaded after explicit loading", [
                    "id" => $this->category->id,
                    "name" => $this->category->name,
                ]);
            }

            // Check if the category's shareTo method is available
            if (!method_exists($this->category, "shareTo")) {
                Log::error("shareTo method not found on Category model");
                return false;
            }

            // 実際の共有処理
            Log::info("About to call category->shareTo", [
                "category_id" => $this->category->id,
                "user_id" => $recipientUser->id,
                "permission" => $this->permission,
            ]);

            // トランザクションで処理
            DB::beginTransaction();
            try {
                $this->category->shareTo($recipientUser, $this->permission);
                DB::commit();
                Log::info(
                    "Category shared successfully - transaction committed"
                );
                return true;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error in transaction: " . $e->getMessage(), [
                    "trace" => $e->getTraceAsString(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error(
                "Error processing category sharing: " . $e->getMessage(),
                ["trace" => $e->getTraceAsString()]
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
