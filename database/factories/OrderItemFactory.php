<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'product_name' => ucwords(fake()->words(2, true)),
            'product_sku' => strtoupper(fake()->bothify('AN-???-###')),
            'unit_price' => fake()->randomFloat(2, 40, 300),
            'quantity' => fake()->numberBetween(1, 3),
        ];
    }
}
