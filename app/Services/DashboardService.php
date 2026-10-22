<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * All data for the dashboard of one user. Never mixes currencies:
     * every amount stays in the currency it was issued in.
     */
    public function build(int $userId): array
    {
        $revenueByCurrency = $this->paymentTotalsByCurrency($userId);
        $primaryCurrency = $this->primaryCurrency($userId);

        return [
            'totalInvoices' => $this->totalInvoices($userId),
            'revenueByCurrency' => $revenueByCurrency,
            'invoicedThisMonth' => $this->invoicedThisMonth($userId),
            'invoiceCountThisMonth' => $this->invoiceCountThisMonth($userId),
            'customerCount' => $this->customerCount($userId),
            'customerBreakdown' => $this->customerBreakdown($userId),
            'chart' => $this->revenueChart($userId, $primaryCurrency),
            'topCustomers' => $this->topCustomers($userId),
            'recentInvoices' => $this->recentInvoices($userId),
        ];
    }

    /**
     * Statement data: invoices and payments grouped by currency.
     */
    public function statement(int $userId): array
    {
        $invoices = Invoice::query()
            ->ownedBy($userId)
            ->with(['customer', 'payments'])
            ->withSum('payments as paid_total_sum', 'amount')
            ->orderBy('id')
            ->get();

        $payments = Payment::query()
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->where('payments.user_id', $userId)
            ->orderBy('payments.payment_date')
            ->get(['payments.*', 'invoices.currency as currency', 'invoices.invoice_number as invoice_number']);

        return [
            'currencyGroups' => $invoices->groupBy('currency')->map(fn ($group) => [
                'invoices' => $group,
                'grandTotal' => (float) $group->sum('grand_total'),
                'paidTotal' => (float) $group->sum(fn ($invoice) => $invoice->paid_total),
                'balanceTotal' => (float) $group->sum('balance'),
                'payments' => $payments->where('currency', $group->first()->currency)->values(),
            ]),
        ];
    }

    /**
     * Sum of received payments grouped by currency (single aggregate query).
     */
    protected function paymentTotalsByCurrency(int $userId): Collection
    {
        return Payment::query()
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->where('payments.user_id', $userId)
            ->groupBy('invoices.currency')
            ->orderByDesc(DB::raw('SUM(payments.amount)'))
            ->pluck(DB::raw('SUM(payments.amount)'), 'invoices.currency');
    }

    /**
     * The primary currency based on invoice volume (fallback: INR).
     */
    protected function primaryCurrency(int $userId): string
    {
        return Invoice::query()->ownedBy($userId)
            ->selectRaw('currency, COUNT(*) as cnt, SUM(grand_total) as total')
            ->groupBy('currency')
            ->orderByDesc('total')
            ->orderByDesc('cnt')
            ->value('currency') ?? 'INR';
    }

    protected function totalInvoices(int $userId): int
    {
        return Invoice::query()->ownedBy($userId)->count();
    }

    /**
     * Grand totals of invoices dated in the current month, per currency.
     */
    protected function invoicedThisMonth(int $userId): Collection
    {
        return Invoice::query()
            ->ownedBy($userId)
            ->whereBetween('invoice_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->groupBy('currency')
            ->selectRaw('currency, SUM(grand_total) as total')
            ->pluck('total', 'currency');
    }

    protected function invoiceCountThisMonth(int $userId): int
    {
        return Invoice::query()
            ->ownedBy($userId)
            ->whereBetween('invoice_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();
    }

    protected function customerCount(int $userId): int
    {
        return Customer::query()->ownedBy($userId)->count();
    }

    /**
     * Companies vs individuals counts, e.g. ["22 companies · 12 individuals"].
     */
    protected function customerBreakdown(int $userId): array
    {
        $counts = Customer::query()
            ->ownedBy($userId)
            ->groupBy('customer_type')
            ->selectRaw('customer_type, COUNT(*) as cnt')
            ->pluck('cnt', 'customer_type');

        return [
            'business' => (int) ($counts['business'] ?? 0),
            'individual' => (int) ($counts['individual'] ?? 0),
        ];
    }

    /**
     * Invoices created of the primary currency over the last 6 months,
     * zero-filled, with rounded bar percentages and y-axis ticks.
     */
    protected function revenueChart(int $userId, string $currency): array
    {
        $start = now()->startOfMonth()->subMonths(5); // earliest month on the chart

        $monthly = Invoice::query()
            ->ownedBy($userId)
            ->where('currency', $currency)
            ->where(function ($q) use ($start) {
                $q->where('invoice_date', '>=', $start->toDateString())
                    ->orWhere(function ($q2) use ($start) {
                        $q2->whereNull('invoice_date')
                            ->where('created_at', '>=', $start->toDateString());
                    });
            })
            ->groupBy('month_key')
            ->pluck(
                DB::raw('SUM(grand_total)'),
                DB::raw("DATE_FORMAT(COALESCE(invoice_date, created_at), '%Y-%m') as month_key")
            );

        $currentMonth = now()->format('Y-m');
        $max = 0.0;
        $months = [];

        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $value = (float) ($monthly[$month->format('Y-m')] ?? 0);
            $max = max($max, $value);

            $months[] = [
                'key' => $month->format('Y-m'),
                'label' => $month->format('M'),
                'value' => $value,
                'isCurrent' => $month->format('Y-m') === $currentMonth,
            ];
        }

        // Nice y-axis ceiling in steps that divide into 4 ticks.
        $step = $max > 0 ? $this->niceStep($max / 4) : 1;
        $axisMax = $step * 4;

        foreach ($months as &$month) {
            $month['valueLabel'] = $this->compactNumber($month['value']);
            $month['pct'] = $max > 0 ? max(2, (int) round($month['value'] / $axisMax * 100)) : 0;
        }
        unset($month);

        return [
            'currency' => $currency,
            'months' => $months,
            'ticks' => array_map(
                fn ($t) => ['label' => $this->compactNumber($t * $step), 'value' => $t / 4],
                [0, 1, 2, 3, 4]
            ),
        ];
    }

    /**
     * Value/messages rounded to a "nice" step size (e.g. 40k).
     */
    protected function niceStep(float $raw): float
    {
        $magnitude = pow(10, floor(log10(max($raw, 1))));

        return ceil($raw / $magnitude) * $magnitude;
    }

    /**
     * 92000 => "92k", 1203600 => "12L"-style compact labels for the chart.
     */
    protected function compactNumber(float $value): string
    {
        if ($value >= 10000000) {
            return rtrim(rtrim(sprintf('%.1f', $value / 10000000), '0'), '.').'Cr';
        }

        if ($value >= 100000) {
            return rtrim(rtrim(sprintf('%.1f', $value / 100000), '0'), '.').'L';
        }

        if ($value >= 1000) {
            return rtrim(rtrim(sprintf('%.1f', $value / 1000), '0'), '.').'k';
        }

        return (string) (int) $value;
    }

    /**
     * Top 5 customers by total invoiced. Each customer is displayed in
     * their dominant currency; invoice counts cover all currencies.
     */
    protected function topCustomers(int $userId): Collection
    {
        $rows = Invoice::query()
            ->ownedBy($userId)
            ->groupBy('customer_id', 'currency')
            ->selectRaw('customer_id, currency, COUNT(*) as cnt, SUM(grand_total) as total')
            ->get();

        $customerIds = $rows->pluck('customer_id')->unique();
        $customers = Customer::query()
            ->ownedBy($userId)
            ->whereIn('id', $customerIds)
            ->get()
            ->keyBy('id');

        // Build aggregates in one pass (customer objects resolved below).
        $aggregates = $rows->groupBy('customer_id')->map(function ($currencyRows) {
            $counts = (int) $currencyRows->sum('cnt');
            $byCurrency = $currencyRows->pluck('total', 'currency');

            return [
                'counts' => $counts,
                'byCurrency' => $byCurrency,
                'primaryAmount' => $byCurrency->isEmpty() ? 0.0 : (float) $byCurrency->values()->first(),
                'primaryCurrency' => $byCurrency->keys()->first(),
            ];
        });

        return collect($aggregates)
            ->map(function ($agg, $customerId) use ($customers) {
                $customer = $customers->get($customerId);

                if (! $customer) {
                    return null;
                }

                return [
                    'id' => $customer->id,
                    'name' => $customer->display_name,
                    'initials' => $this->initials($customer->display_name),
                    'country' => $customer->country_code ? strtoupper($customer->country_code) : null,
                    'invoiceCount' => $agg['counts'],
                    'amount' => $agg['primaryAmount'],
                    'currency' => $agg['primaryCurrency'],
                    'showUrl' => route('customers.show', $customer),
                ];
            })
            ->filter()
            ->sortByDesc(fn ($row) => $row['amount'])
            ->take(5)
            ->values();
    }

    /**
     * Latest 5 invoices with customer and payment aggregate.
     */
    protected function recentInvoices(int $userId): Collection
    {
        return Invoice::query()
            ->ownedBy($userId)
            ->with(['customer'])
            ->withSum('payments as paid_total_sum', 'amount')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function ($invoice) {
                $invoice->view_url = route('invoices.show', $invoice);
                $invoice->pdf_url = route('invoices.pdf', $invoice);

                return $invoice;
            });
    }

    /**
     * Two-letter initials for avatars, e.g. "Priya Mehta" => "PM".
     */
    protected function initials(string $name): string
    {
        $words = collect(preg_split('/\s+/', trim($name)))->filter()->values();

        $first = mb_strtoupper(mb_substr($words->first() ?? '', 0, 1));
        $last = $words->count() > 1
            ? mb_strtoupper(mb_substr($words->last(), 0, 1))
            : '';

        return $first.$last;
    }
}
