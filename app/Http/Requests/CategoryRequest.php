<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the category form for both creating and updating.
 */
class CategoryRequest extends FormRequest
{
    /**
     * Category routes already require a signed-in administrator.
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
            // 60 characters, like the categories.name column; the category being edited may keep its name.
            'name' => ['required', 'string', 'max:60', Rule::unique('categories', 'name')->ignore($this->route('category'))],
        ];
    }
}
