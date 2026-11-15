<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the Store Settings form.
 */
class StoreSettingRequest extends FormRequest
{
    /**
     * Settings routes already require a signed-in administrator.
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
            'store_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            // Up to 2 MB (the "max" rule counts kilobytes for files).
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'store_name' => 'store name',
            'contact_email' => 'contact email',
            'logo' => 'store logo',
        ];
    }

    /**
     * Emails are stored in lower case, as on the profile and "Add user" forms.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('contact_email'))) {
            $this->merge(['contact_email' => strtolower(trim($this->input('contact_email')))]);
        }
    }
}
