<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the product form for both creating and updating.
 */
class ProductRequest extends FormRequest
{
    /**
     * Product routes already require a signed-in administrator.
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
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9-]+$/', Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'brand' => ['required', 'string', 'max:100'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'concentration' => ['required', Rule::in(Product::CONCENTRATIONS)],
            'volume_ml' => ['required', 'integer', 'min:1', 'max:1000'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.regex' => 'The SKU may only contain letters, numbers and dashes.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'category_id' => 'category',
            'volume_ml' => 'volume',
            'is_active' => 'status',
        ];
    }

    /**
     * SKUs are stored in upper case so "an-flr-001" and "AN-FLR-001" count as the same code.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('sku'))) {
            $this->merge(['sku' => strtoupper(trim($this->input('sku')))]);
        }
    }
}
