<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TodoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "title" => "required|string|max:255",
            "category_id" => "nullable|exists:categories,id",
            "due_date" => "nullable|date",
            "due_time" => "nullable|string",
            "recurrence_type" => "nullable|in:none,daily,weekly,monthly",
            "recurrence_end_date" => "nullable|date|after_or_equal:due_date",
        ];
    }

    public function messages(): array
    {
        return [
            "title.required" => "タイトルは必須です",
            "title.max" => "タイトルは255文字以内で入力してください",
            "due_time.date_format" => "時刻の形式が正しくありません",
            "category_id.exists" => "選択されたカテゴリーは存在しません",
            "recurrence_end_date.after_or_equal" =>
                "繰り返し終了日は開始日以降である必要があります",
        ];
    }
}
