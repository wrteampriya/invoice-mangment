<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 500, 50000),
            'payment_date' => fake()->dateTimeBetween('-6 months')->format('Y-m-d'),
            'method' => fake()->randomElement([
                \App\Models\Payment::METHODS,
            ]),
            'reference' => fake()->regexify('[A-Z0-9]{8}'),
        ];
    }
}
