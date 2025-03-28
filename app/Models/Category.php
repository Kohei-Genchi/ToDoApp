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
        $result = $this->sharedWith->contains($user);
        Log::info("Category::isSharedWith check", [
            "category_id" => $this->id,
            "user_id" => $user->id,
            "result" => $result ? "yes" : "no",
        ]);
        return $result;
    }

    /**
     * Share this category with a user
     */
    // In Category.php
    // Category.php
    // shareTo メソッドを徹底的にデバッグ
    public function shareTo(User $user, string $permission = "view"): void
    {
        Log::info("Category::shareTo called", [
            "category_id" => $this->id,
            "user_id" => $user->id,
            "permission" => $permission,
        ]);

        try {
            // Check if it's already shared with this user
            $isShared = $this->isSharedWith($user);
            Log::info("Category already shared?", [
                "already_shared" => $isShared ? "yes" : "no",
            ]);

            if (!$isShared) {
                // 明示的なSQLも記録
                $sql =
                    "INSERT INTO category_shares (category_id, user_id, permission, created_at, updated_at) VALUES (?, ?, ?, ?, ?)";
                $params = [$this->id, $user->id, $permission, now(), now()];
                Log::info("Executing SQL", [
                    "sql" => $sql,
                    "params" => $params,
                ]);

                // Eloquentリレーションを使用
                $result = $this->sharedWith()->attach($user, [
                    "permission" => $permission,
                ]);
                Log::info("Category attach result", ["result" => $result]);
            } else {
                // Update permission if already shared
                $result = $this->sharedWith()->updateExistingPivot($user->id, [
                    "permission" => $permission,
                ]);
                Log::info("Category update permission result", [
                    "rows_affected" => $result,
                ]);
            }

            // 結果を確認
            $check = DB::table("category_shares")
                ->where("category_id", $this->id)
                ->where("user_id", $user->id)
                ->first();

            Log::info("Verification after sharing", [
                "found_in_db" => $check ? "yes" : "no",
                "data" => $check,
            ]);
        } catch (\Exception $e) {
            Log::error("Error in Category::shareTo: " . $e->getMessage(), [
                "trace" => $e->getTraceAsString(),
            ]);
            throw $e; // 呼び出し元でキャッチできるように再スロー
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
