<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryApiRequest extends FormRequest
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
            "color" => "required|string|max:7", // For hex color codes (#RRGGBB)
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            "name.required" => "Category name is required",
            "name.max" => "Category name must not exceed 255 characters",
            "color.required" => "Color is required",
            "color.max" => "Color must be a valid hex code (max 7 characters)",
        ];
    }

    /**
     * Get the error messages for the defined validation rules for JSON response.
     */
    public function failedValidation(
        \Illuminate\Contracts\Validation\Validator $validator
    ) {
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
}
