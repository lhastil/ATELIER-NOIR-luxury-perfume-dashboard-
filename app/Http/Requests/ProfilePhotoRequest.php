<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a new profile photo for the signed-in user.
 */
class ProfilePhotoRequest extends FormRequest
{
    /**
     * Profile routes already require a signed-in user, who can only change their own photo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // JPEG, PNG or WebP up to 2 MB ("max" counts kilobytes for files), like product images.
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'photo' => 'profile photo',
        ];
    }
}
