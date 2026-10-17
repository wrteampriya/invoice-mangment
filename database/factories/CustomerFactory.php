<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use App\Support\Countries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(Customer::CUSTOMER_TYPES);
        $isBusiness = $type === 'business';

        return [
            'user_id' => User::factory(),
            'customer_type' => $type,
            'name' => fake()->name(),
            'company_name' => $isBusiness ? fake()->company() : null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'tax_id_type' => $isBusiness ? fake()->randomElement(['GSTIN', 'PAN', 'VAT', 'EIN']) : null,
            'tax_id' => $isBusiness ? fake()->bothify('??##########') : null,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->randomElement(Countries::codes()),
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'individual',
            'company_name' => null,
            'tax_id_type' => null,
            'tax_id' => null,
        ]);
    }

    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'business',
        ]);
    }
}
