<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'invoice_number' => 'INV-' . str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'invoice_date' => fake()->date(),
            'due_date' => fake()->date(),
            'currency' => 'USD',
            'customer_country' => fake()->randomElement(['US', 'IN', 'GB']),
            'tax_type' => null,
            'tax_rate' => null,
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 0,
            'terms_and_conditions' => null,
        ];
    }
}
