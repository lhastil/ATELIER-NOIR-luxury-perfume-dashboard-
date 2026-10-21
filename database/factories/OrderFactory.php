<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'ORD-'.fake()->unique()->numberBetween(20000, 99999),
            'customer_id' => Customer::factory(),
            'status' => 'pending',
            'placed_at' => now(),
        ];
    }
}
