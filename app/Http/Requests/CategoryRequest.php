<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name" => "required|string|max:255",
            "color" => "required|string|max:7",
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            "name.required" => $this->expectsJson()
                ? "Category name is required"
                : "カテゴリー名は必須です",
            "name.max" => $this->expectsJson()
                ? "Category name must not exceed 255 characters"
                : "カテゴリー名は255文字以内で入力してください",
            "color.required" => $this->expectsJson()
                ? "Color is required"
                : "カラーは必須です",
            "color.max" => $this->expectsJson()
                ? "Color must be a valid hex code (max 7 characters)"
                : "カラーは7文字以内で入力してください",
        ];
    }

    /**
     * Handle a failed validation attempt for JSON requests.
     */
    public function failedValidation(
        \Illuminate\Contracts\Validation\Validator $validator
    ) {
        if ($this->expectsJson()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json(
                    [
                        "success" => false,
                        "message" => "Validation failed",
                        "errors" => $validator->errors(),
                    ],
                    422
                )
            );
        }

        parent::failedValidation($validator);
    }
}
