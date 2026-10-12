<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilePhotoRequest extends FormRequest
{
    /**
     * Only registrar staff and administrators have a profile photo.
     */
    public function authorize(): bool
    {
        return $this->user()->isOfficeUser();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.max' => __('The photo must not be larger than 2 MB.'),
            'photo.mimes' => __('The photo must be a JPG, PNG or WebP image.'),
        ];
    }
}
