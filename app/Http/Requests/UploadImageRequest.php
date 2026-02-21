<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
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
        $maxSizeKB = config('upload.max_file_size') / 1024;
        $allowedMimes = ['jpeg', 'png', 'jpg', 'gif', 'webp'];

        return [
            'image' => [
                'required',
                'image',
                'mimes:' . implode(',', $allowedMimes),
                'max:' . $maxSizeKB,
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Please select an image to upload.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Only JPEG, PNG, GIF and WebP images are allowed.',
            'image.max' => 'The image size must not exceed ' . (config('upload.max_file_size') / 1024 / 1024) . 'MB.',
        ];
    }
}
