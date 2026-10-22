<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_type' => 'individual',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+91 98765 43210',
            'address' => '123 Main Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country_code' => 'in',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('customers.index'))->assertRedirect(route('login'));
        $this->get(route('customers.create'))->assertRedirect(route('login'));
        $this->post(route('customers.store'))->assertRedirect(route('login'));
        $this->get(route('customers.show', $customer))->assertRedirect(route('login'));
        $this->get(route('customers.edit', $customer))->assertRedirect(route('login'));
        $this->put(route('customers.update', $customer))->assertRedirect(route('login'));
        $this->delete(route('customers.destroy', $customer))->assertRedirect(route('login'));
    }

    public function test_user_can_create_individual_customer_and_business_fields_are_nulled(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), $this->validPayload([
            'company_name' => 'Should Be Ignored Ltd',
            'tax_id_type' => 'GSTIN',
            'tax_id' => '27ABCDE1234F1Z5',
        ]));

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('status');

        $customer = Customer::where('user_id', $user->id)->first();
        $this->assertNotNull($customer);
        $this->assertSame('individual', $customer->customer_type);
        $this->assertNull($customer->company_name);
        $this->assertNull($customer->tax_id_type);
        $this->assertNull($customer->tax_id);
        $this->assertSame('john@example.com', $customer->email);
        $this->assertSame('IN', $customer->country_code);
    }

    public function test_business_customer_requires_company_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), $this->validPayload([
            'customer_type' => 'business',
        ]));

        $response->assertSessionHasErrors('company_name');
        $this->assertSame(0, Customer::count());
    }

    public function test_tax_id_is_required_when_tax_id_type_is_set(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), $this->validPayload([
            'customer_type' => 'business',
            'company_name' => 'Acme Ltd',
            'tax_id_type' => 'GSTIN',
        ]));

        $response->assertSessionHasErrors('tax_id');

        $response = $this->actingAs($user)->post(route('customers.store'), $this->validPayload([
            'customer_type' => 'business',
            'company_name' => 'Acme Ltd',
            'tax_id_type' => 'GSTIN',
            'tax_id' => '27ABCDE1234F1Z5',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Customer::count());
    }

    public function test_invalid_country_code_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), $this->validPayload([
            'country_code' => 'XX',
        ]));

        $response->assertSessionHasErrors('country_code');
        $this->assertSame(0, Customer::count());
    }

    public function test_user_sees_only_own_customers_in_list_and_search(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Customer::factory()->individual()->create([
            'user_id' => $user->id,
            'name' => 'My Own Customer',
            'email' => 'mine-own@example.com',
        ]);
        Customer::factory()->individual()->create([
            'user_id' => $other->id,
            'name' => 'Zzz Other Customer',
            'email' => 'zzz-other@example.com',
        ]);

        // The list page renders rows via the JSON feed, so isolation is
        // asserted against the same endpoint the table loads.
        $response = $this->actingAs($user)->getJson(route('customers.index', ['format' => 'json']));
        $response->assertOk();
        $response->assertJsonFragment(['raw_name' => 'My Own Customer']);
        $response->assertJsonMissing(['raw_name' => 'Zzz Other Customer']);

        $response = $this->actingAs($user)->getJson(route('customers.index', ['format' => 'json', 'search' => 'Customer']));
        $response->assertOk();
        $response->assertJsonFragment(['raw_name' => 'My Own Customer']);
        $response->assertJsonMissing(['email' => 'zzz-other@example.com']);

        $response = $this->actingAs($user)->getJson(route('customers.index', ['format' => 'json', 'search' => 'Zzz Other']));
        $response->assertOk();
        $response->assertJsonMissing(['raw_name' => 'Zzz Other Customer']);
        $response->assertJsonMissing(['email' => 'zzz-other@example.com']);
    }

    public function test_index_can_return_json_format_for_bootstrap_table(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->individual()->create([
            'user_id' => $user->id,
            'name' => 'Table Test Customer',
            'email' => 'table@example.com',
        ]);

        $response = $this->actingAs($user)->getJson(route('customers.index', ['format' => 'json']));
        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $customer->id,
            'raw_name' => 'Table Test Customer',
            'email' => 'table@example.com',
        ]);
    }

    public function test_create_page_has_searchable_country_select_and_back_button(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('customers.create'));

        $response->assertOk();
        $response->assertSee('id="country_search"', false);
        $response->assertSee('Back', false);
        $response->assertDontSee('breadcrumb');
    }

    public function test_user_gets_403_for_another_users_customer(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->get(route('customers.show', $customer))->assertForbidden();
        $this->actingAs($user)->get(route('customers.edit', $customer))->assertForbidden();
        $this->actingAs($user)->put(route('customers.update', $customer), $this->validPayload())
            ->assertForbidden();
        $this->actingAs($user)->delete(route('customers.destroy', $customer))->assertForbidden();
    }

    public function test_user_id_in_payload_is_ignored(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post(route('customers.store'), $this->validPayload([
            'user_id' => $other->id,
        ]))->assertSessionHasNoErrors();

        $customer = Customer::first();
        $this->assertSame($user->id, $customer->user_id);
    }

    public function test_owner_can_update_and_delete(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

        $this->actingAs($user)->put(route('customers.update', $customer), $this->validPayload([
            'name' => 'New Name',
        ]))->assertRedirect(route('customers.index'));

        $this->assertSame('New Name', $customer->fresh()->name);

        $this->actingAs($user)->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }
}
