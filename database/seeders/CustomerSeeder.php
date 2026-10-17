<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Seed demo customers for the test user.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if (! $user || $user->customers()->exists()) {
            return;
        }

        Customer::factory()->count(15)->create([
            'user_id' => $user->id,
        ]);
    }
}
