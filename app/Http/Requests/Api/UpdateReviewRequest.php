<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'meal_id' => ['sometimes', 'integer', 'exists:meals,id'],

            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],

            'comment' => ['nullable', 'string', 'max:1000'],

            'images' => ['nullable', 'array'],

            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }
    public function messages(): array
    {
        return [
            'meal_id.integer' => 'Meal ID must be an integer.',
            'meal_id.exists' => 'The selected meal does not exist.',

            'rating.integer' => 'Rating must be an integer.',
            'rating.min' => 'Rating must be at least 1.',
            'rating.max' => 'Rating may not be greater than 5.',

            'comment.string' => 'Comment must be a string.',
            'comment.max' => 'Comment may not be greater than 1000 characters.',

            'images.array' => 'Images must be an array.',
            'images.*.image' => 'Each image must be a valid image file.',
            'images.*.mimes' => 'Each image must be a JPEG, PNG, JPG, GIF, or WEBP file.',
            'images.*.max' => 'Each image may not be greater than 2MB.',
        ];
    }
}
