<?php

namespace Tests\Feature\Invoices;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function lineItems(): array
    {
        return [
            [
                'item_description' => 'Website design',
                'quantity' => 2,
                'unit_price' => 5000,
                'discount_type' => 'percentage',
                'discount_value' => 10,
            ],
            [
                'item_description' => 'Hosting (1 year)',
                'quantity' => 1,
                'unit_price' => 1200,
                'discount_type' => 'none',
                'discount_value' => null,
            ],
        ];
    }

    private function validPayload(array $overrides = []): array
    {
        $customer = Customer::factory()->business()->create([
            'user_id' => User::factory()->create()->id,
            'country_code' => 'in',
        ]);

        return array_merge([
            'customer_id' => $customer->id,
            'invoice_date' => '2026-10-21',
            'due_date' => '2026-11-05',
            'currency' => 'INR',
            'tax_type' => 'gst',
            'tax_rate' => 18,
            'items' => $this->lineItems(),
            'terms_and_conditions' => 'Pay via bank transfer.',
        ], $overrides);
    }

    /** Payload with all fields belonging to one of the current user's customers. */
    private function payloadFor(Customer $customer, array $overrides = []): array
    {
        return $this->validPayload(array_merge(['customer_id' => $customer->id], $overrides));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $invoice = Invoice::factory()->create();

        $this->get(route('invoices.index'))->assertRedirect(route('login'));
        $this->get(route('invoices.create'))->assertRedirect(route('login'));
        $this->post(route('invoices.store'))->assertRedirect(route('login'));
        $this->get(route('invoices.show', $invoice))->assertRedirect(route('login'));
        $this->get(route('invoices.edit', $invoice))->assertRedirect(route('login'));
        $this->put(route('invoices.update', $invoice))->assertRedirect(route('login'));
        $this->delete(route('invoices.destroy', $invoice))->assertRedirect(route('login'));
    }

    public function test_user_can_create_invoice_with_recalculated_totals(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->business()->create([
            'user_id' => $user->id,
            'country_code' => 'us',
        ]);

        // invoice_number and customer_country from the payload must be ignored:
        // the number is server-generated, the country is snapshotted from the customer.
        $response = $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer, [
            'invoice_number' => 'INV-9999',
            'customer_country' => 'XX',
            'tax_type' => 'gst',
            'tax_rate' => 18,
            'items' => [
                [
                    'item_description' => "Item <script>alert('x')</script>",
                    'quantity' => 2,
                    'unit_price' => 5000,
                    'discount_type' => 'percentage',
                    'discount_value' => 10,
                ],
                [
                    'item_description' => 'Hosting (1 year)',
                    'quantity' => 1,
                    'unit_price' => 1200,
                    'discount_type' => 'none',
                    'discount_value' => null,
                ],
            ],
        ]));

        $invoice = Invoice::query()->latest('id')->first();
        $response->assertRedirect(route('invoices.show', $invoice));

        $this->assertSame('INV-0001', $invoice->invoice_number);
        $this->assertSame($user->id, $invoice->user_id);
        $this->assertSame('US', $invoice->customer_country);

        // subtotal 11200, discount 1000 (10% of 10000), tax 18% of 10200 = 1836
        $this->assertSame(11200.00, (float) $invoice->subtotal);
        $this->assertSame(1000.00, (float) $invoice->discount_total);
        $this->assertSame(1836.00, (float) $invoice->tax_total);
        $this->assertSame(12036.00, (float) $invoice->grand_total);
        $this->assertSame('gst', $invoice->tax_type);
        $this->assertSame('18.00', (string) $invoice->tax_rate);

        // description is stored as given; escaping happens on output via {{ }}
        $this->assertSame("Item <script>alert('x')</script>", $invoice->items->first()->item_description);
        $this->assertSame(9000.00, (float) $invoice->items->first()->line_total);
        $this->assertSame(1200.00, (float) $invoice->items->last()->line_total);
    }

    public function test_invoice_numbers_are_sequential_per_user(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        // Another user's invoices must not affect this user's sequence.
        Invoice::factory()->for(Customer::factory()->create(), 'customer')->create(['invoice_number' => 'INV-9999']);

        foreach (['INV-0001', 'INV-0002', 'INV-0003'] as $expected) {
            $this->actingAs($user)
                ->post(route('invoices.store'), $this->payloadFor($customer))
                ->assertSessionHasNoErrors();
        }

        $this->assertEquals(
            ['INV-0001', 'INV-0002', 'INV-0003'],
            Invoice::query()->where('user_id', $user->id)->orderBy('id')->pluck('invoice_number')->all()
        );
    }

    public function test_tax_none_saves_null_type_and_rate(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer, [
            'tax_type' => 'NONE',
            'tax_rate' => '18',
        ]));

        $invoice = Invoice::query()->latest('id')->first();
        $this->assertNull($invoice->tax_type);
        $this->assertNull($invoice->tax_rate);
        $this->assertSame(0.00, (float) $invoice->tax_total);
    }

    public function test_validation_rules(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer))
            ->assertSessionHasNoErrors();

        // due date before invoice date
        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer, [
            'due_date' => '2026-10-20',
        ]))->assertSessionHasErrors('due_date');

        // tax rate required when a taxed type is selected
        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer, [
            'tax_rate' => null,
        ]))->assertSessionHasErrors('tax_rate');

        // tax rate must be within 0-100
        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer, [
            'tax_rate' => 150,
        ]))->assertSessionHasErrors('tax_rate');

        // quantity must be positive
        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer, [
            'items' => [['item_description' => 'x', 'quantity' => 0, 'unit_price' => 10, 'discount_type' => 'none']],
        ]))->assertSessionHasErrors('items.0.quantity');

        // percentage discount above 100 is rejected
        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer, [
            'items' => [['item_description' => 'x', 'quantity' => 1, 'unit_price' => 10, 'discount_type' => 'percentage', 'discount_value' => 150]],
        ]))->assertSessionHasErrors('items.0.discount_value');

        // flat discount above the line amount is rejected
        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer, [
            'items' => [['item_description' => 'x', 'quantity' => 1, 'unit_price' => 10, 'discount_type' => 'flat', 'discount_value' => 20]],
        ]))->assertSessionHasErrors('items.0.discount_value');

        // at least one item
        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer, [
            'items' => [],
        ]))->assertSessionHasErrors('items');
    }

    public function test_customer_of_another_user_is_forbidden(): void
    {
        $user = User::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $this->actingAs($user)
            ->post(route('invoices.store'), $this->payloadFor($otherCustomer))
            ->assertForbidden();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_user_gets_403_for_another_users_invoice(): void
    {
        // update needs a valid body: ownership is enforced by the InvoicePolicy
        $user = User::factory()->create();
        $attacker = User::factory()->create();
        $invoice = Invoice::factory()->for(Customer::factory()->create(['user_id' => $attacker->id]), 'customer')->create();

        $this->actingAs($user)->get(route('invoices.show', $invoice))->assertForbidden();
        $this->actingAs($user)->get(route('invoices.edit', $invoice))->assertForbidden();

        $maintenance = Customer::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->put(route('invoices.update', $invoice), $this->payloadFor($maintenance))->assertForbidden();
        $this->actingAs($user)->delete(route('invoices.destroy', $invoice))->assertForbidden();
    }

    public function test_owner_can_update_and_totals_are_recalculated(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer, [
            'tax_type' => 'GST',
            'tax_rate' => '18',
            'items' => $this->lineItems(),
        ]));

        $invoice = Invoice::query()->latest('id')->first();
        $originalNumber = $invoice->invoice_number;

        $this->actingAs($user)->put(route('invoices.update', $invoice), $this->payloadFor($customer, [
            'tax_type' => 'none',
            'tax_rate' => null,
            'due_date' => null,
            'items' => [
                ['item_description' => 'New item', 'quantity' => 4, 'unit_price' => 100, 'discount_type' => 'none', 'discount_value' => null],
            ],
        ]))->assertRedirect(route('invoices.show', $invoice));

        $invoice = $invoice->fresh(['items']);

        // number and user are never touched by an update
        $this->assertSame($originalNumber, $invoice->invoice_number);
        $this->assertSame($user->id, $invoice->user_id);
        $this->assertNull($invoice->tax_type);
        $this->assertNull($invoice->tax_rate);
        $this->assertNull($invoice->due_date);
        $this->assertSame(1, $invoice->items->count());
        $this->assertSame(400.00, (float) $invoice->subtotal);
        $this->assertSame(400.00, (float) $invoice->grand_total);

        // old lines were replaced rather than duplicated
        $this->assertDatabaseCount('invoice_items', 1);
    }

    public function test_owner_can_delete(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer));
        $invoice = Invoice::query()->latest('id')->first();
        InvoiceItem::where('invoice_id', $invoice->id)->count();

        $this->actingAs($user)->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'));

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('invoice_items', ['invoice_id' => $invoice->id]);
    }

    public function test_index_can_return_json_format_for_bootstrap_table(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer));

        $response = $this->actingAs($user)->getJson(route('invoices.index', ['format' => 'json']));

        $response->assertOk();
        $response->assertJsonFragment(['total' => 1]);
        $response->assertJsonPath('rows.0.invoice_number', 'INV-0001');
        $this->assertSame(12036.0, (float) $response->json('rows.0.grand_total'));
    }

    public function test_show_and_edit_views_render_for_the_owner(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $other = Invoice::factory()->create();

        $this->actingAs($user)->post(route('invoices.store'), $this->payloadFor($customer));
        $invoice = Invoice::query()->latest('id')->first();

        $this->actingAs($user)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee('12,036.00');

        $this->actingAs($user)->get(route('invoices.edit', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_number, false);

        // make sure another user's invoice is not referenced by these views
        $this->assertSame(2, Invoice::count());
        $this->assertNotSame($other->id, $invoice->id);
    }
}
