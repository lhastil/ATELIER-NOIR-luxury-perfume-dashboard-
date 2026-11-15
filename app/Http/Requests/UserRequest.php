<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validates the "Add user" and "Edit user" forms in User Management.
 */
class UserRequest extends FormRequest
{
    /**
     * Only Store Managers manage accounts and roles. The routes already require
     * this (can:access-users); checking again here keeps role changes safe even
     * if a route were ever moved.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isStoreManager();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = $this->route('user') === null;

        return [
            'name' => ['required', 'string', 'max:255'],
            // On the edit form the user's current address is allowed.
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'role' => ['required', 'string', Rule::in(array_keys(User::ROLES))],
            // A password is set when the account is created; users change it on their own profile.
            'password' => $creating ? ['required', 'string', 'confirmed', Password::defaults()] : ['prohibited'],
            'password_confirmation' => $creating ? ['required', 'string'] : ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'password_confirmation' => 'password confirmation',
        ];
    }

    /**
     * Emails are stored in lower case so "Admin@example.com" and "admin@example.com" count as the same address.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }
}
