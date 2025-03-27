<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "email" => [
                "required",
                "string",
                "lowercase",
                "email",
                "max:255",
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            "morning_reminder_time" => ["nullable", "date_format:H:i"],
            "evening_reminder_time" => ["nullable", "date_format:H:i"],
            "slack_webhook_url" => ["nullable", "string", "url", "max:255"],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            "slack_webhook_url.url" =>
                "Slack Webhook URLは有効なURLである必要があります。",
        ];
    }
}
