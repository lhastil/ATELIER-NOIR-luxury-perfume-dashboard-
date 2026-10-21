<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => ucwords(fake()->words(2, true)),
            'sku' => strtoupper(fake()->unique()->bothify('AN-???-###')),
            'brand' => fake()->company(),
            'concentration' => fake()->randomElement(Product::CONCENTRATIONS),
            'volume_ml' => fake()->randomElement([30, 50, 75, 100]),
            'price' => fake()->randomFloat(2, 40, 300),
            'stock' => fake()->numberBetween(0, 80),
            'is_active' => true,
            'description' => fake()->sentence(),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
