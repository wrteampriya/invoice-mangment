<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo data so the dashboard shows a realistic mix: paid, partial,
 * overdue and sent invoices plus payments spread over recent months.
 * Skips seeding when the target user already has invoices.
 */
class DashboardSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::query()->where('email', 'priya@gmail.com')->first()
            ?? User::query()->first()
            ?? User::factory()->create([
                'name' => 'Priya Mehta',
                'email' => 'priya@gmail.com',
            ]);

        if ($user->invoices()->exists()) {
            $this->command->line("User {$user->email} already has invoices; skipping dashboard demo data.");

            return;
        }

        $service = app(InvoiceService::class);

        $customers = collect([
            ['customer_type' => 'business', 'name' => 'Meera Joshi', 'company_name' => 'Northwind Traders', 'email' => 'accounts@northwind.co.uk', 'phone' => '+44 7700 900123', 'address' => '12 Kings Wharf', 'city' => 'Manchester', 'state' => 'Greater Manchester', 'postal_code' => 'M1 4WB', 'country_code' => 'gb'],
            ['customer_type' => 'business', 'name' => 'Ravi Sharma', 'company_name' => 'Sharma Textiles', 'email' => 'billing@sharmatextiles.in', 'phone' => '+91 98200 11223', 'address' => 'Ring Road Textile Market', 'city' => 'Surat', 'state' => 'Gujarat', 'postal_code' => '395002', 'country_code' => 'in'],
            ['customer_type' => 'business', 'name' => 'Lena Marsh', 'company_name' => 'Brightline LLC', 'email' => 'ap@brightline.us', 'phone' => '+1 415 555 0912', 'address' => '901 Cypress Ave', 'city' => 'San Francisco', 'state' => 'California', 'postal_code' => '94107', 'country_code' => 'us'],
            ['customer_type' => 'individual', 'name' => 'Meera Joshi', 'company_name' => null, 'email' => 'meera.joshi@example.in', 'phone' => '+91 97600 44556', 'address' => 'B-14 Shanti Heights', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country_code' => 'in'],
            ['customer_type' => 'individual', 'name' => 'Arjun Patel', 'company_name' => null, 'email' => 'arjun.patel@example.in', 'phone' => '+91 99887 23344', 'address' => 'Navavas, Madhapar', 'city' => 'Bhuj', 'state' => 'Gujarat', 'postal_code' => '370020', 'country_code' => 'in'],
        ])->map(fn (array $attributes) => $user->customers()->create($attributes));

        $currencyByCountry = ['gb' => 'GBP', 'in' => 'INR', 'us' => 'USD'];

        $plan = [
            // [customer, items, monthsBack, dueInDays, payments => share-of-total, monthsBack]
            [0, [['Website design', 2, 50000]], 2, 20, [['1.2', 1]]],             // paid
            [1, [['Fabric order', 40, 12000], ['Embroidery', 10, 8000]], 1, 25, [['1.05', 2]]], // paid
            [2, [['Consulting retainer', 6, 22000]], 0, 15, [['1.1', 0]]],         // paid (this month)
            [0, [['ERP integration', 1, 480000]], 3, -10, [['0.58', 2]]],          // partial
            [1, [['Bulk saree supply', 120, 4500]], 2, -18, [['0.52', 1]]],        // partial + overdue
            [3, [['Tailoring work', 5, 9000]], 1, 30, [['0.66', 0]]],              // partial (paid partly early)
            [4, [['Interior design', 1, 900000]], 4, -25, [['0.5', 3]]],           // partial + overdue (long due)
            [2, [['API development', 3, 40000]], 5, -30, [['0.85', 4]]],           // overdue, big payments history
            [1, [['Spring catalogue', 1, 250000]], 0, 45, []],                     // sent (upcoming)
            [3, [['Alterations bundle', 8, 3000]], 5, 40, []],                     // sent (upcoming)
        ];

        foreach ($plan as [$customerIndex, $items, $monthsBack, $dueInDays, $payments]) {
            $customer = $customers[$customerIndex];
            $currency = $currencyByCountry[$customer->country_code] ?? 'INR';
            $issuedAt = now()->subMonths($monthsBack)->startOfMonth()->addDays(3);
            $dueAt = $dueInDays >= 0
                ? $issuedAt->copy()->addDays($dueInDays)
                : now()->subDays(abs($dueInDays));

            $invoice = $service->createInvoice($user->id, [
                'customer_id' => $customer->id,
                'invoice_date' => $issuedAt->toDateString(),
                'due_date' => $dueAt->toDateString(),
                'currency' => $currency,
                'tax_type' => $currency === 'INR' ? 'gst' : 'none',
                'tax_rate' => $currency === 'INR' ? 18 : null,
                'terms_and_conditions' => 'Payment due within the stated period. Bank transfer preferred.',
            ], collect($items)->map(fn ($item) => [
                'item_description' => $item[0],
                'quantity' => $item[1],
                'unit_price' => $item[2],
                'discount_type' => 'none',
                'discount_value' => null,
            ])->all());

            foreach ($payments as [$share, $paidMonthsBack]) {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => $user->id,
                    'amount' => min((float) $share * (float) $invoice->grand_total, (float) $invoice->grand_total),
                    'payment_date' => Carbon::now()->subMonths($paidMonthsBack)->addDays(5)->toDateString(),
                    'method' => $currency === 'INR' ? 'upi' : 'bank',
                    'reference' => strtoupper('demo-' . substr(uniqid(), -7)),
                ]);
            }
        }

        $this->command->info('Dashboard demo data created for ' . $user->email . ': '
            . $user->invoices()->count() . ' invoices, ' . Payment::query()->count() . ' payments.');
    }
}
