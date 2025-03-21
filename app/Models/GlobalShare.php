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
     * タスクを共有したユーザー（所有者）を取得
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    /**
     * タスクを共有された側のユーザーを取得
     *
     * @return BelongsTo
     */
    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(User::class, "shared_with_user_id");
    }
}
