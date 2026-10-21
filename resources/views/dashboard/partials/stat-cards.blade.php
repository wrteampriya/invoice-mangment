{{-- Four overview stat cards; amounts are always per currency --}}
@php
    $revenueLines = $revenueByCurrency->isNotEmpty()
        ? [$revenueByCurrency->sortDesc()->keys()->first() => $revenueByCurrency->sortDesc()->first()]
        : ['INR' => 0.0];
    $revenueSecondary = $revenueByCurrency->sortDesc()->slice(1);
@endphp
<div class="stat-grid">
    <div class="dash-card stat-card">
        <span class="stat-icon is-blue-soft"><i class="mdi mdi-receipt-text-outline"></i></span>
        <div>
            <p class="stat-label">Total invoices</p>
            <p class="stat-value mb-0">{{ $totalInvoices }}</p>
            <p class="stat-sub mb-0">All time</p>
        </div>
    </div>

    <div class="dash-card stat-card">
        <span class="stat-icon is-green-soft"><i class="mdi mdi-cash-multiple"></i></span>
        <div>
            <p class="stat-label">Total revenue</p>
            <p class="stat-value mb-0">
                @forelse ($revenueLines as $currency => $amount)
                    {{ format_money($amount, $currency) }}
                @empty
                    {{ format_money(0, 'INR') }}
                @endforelse
            </p>
            <p class="stat-sub mb-0">
                @if ($revenueSecondary->isNotEmpty())
                    {{ $revenueSecondary->map(fn ($amount, $currency) => format_money($amount, $currency))->implode(' · ') }}
                @else
                    Received payments
                @endif
            </p>
        </div>
    </div>

    <div class="dash-card stat-card">
        <span class="stat-icon is-purple-soft"><i class="mdi mdi-chart-bar"></i></span>
        <div>
            <p class="stat-label">Invoiced this month</p>
            <p class="stat-value mb-0">
                @if ($invoicedThisMonth->isEmpty())
                    {{ format_money(0, 'INR') }}
                @else
                    {{ format_money($invoicedThisMonth->sortDesc()->first(), $invoicedThisMonth->sortDesc()->keys()->first()) }}
                @endif
            </p>
            <p class="stat-sub mb-0">
                {{ $invoiceCountThisMonth }} invoice{{ $invoiceCountThisMonth === 1 ? '' : 's' }}
            </p>
        </div>
    </div>

    <div class="dash-card stat-card">
        <span class="stat-icon is-orange-soft"><i class="mdi mdi-account-group-outline"></i></span>
        <div>
            <p class="stat-label">Customers</p>
            <p class="stat-value mb-0">{{ $customerCount }}</p>
            <p class="stat-sub mb-0">
                @if ($customerBreakdown['business'] > 0 || $customerBreakdown['individual'] > 0)
                    @if ($customerBreakdown['business'] > 0)
                        {{ $customerBreakdown['business'] }} compan{{ $customerBreakdown['business'] === 1 ? 'y' : 'ies' }}
                    @endif
                    @if ($customerBreakdown['business'] > 0 && $customerBreakdown['individual'] > 0)
                        <span class="dot-sep">·</span>
                    @endif
                    @if ($customerBreakdown['individual'] > 0)
                        {{ $customerBreakdown['individual'] }} individual{{ $customerBreakdown['individual'] === 1 ? '' : 's' }}
                    @endif
                @else
                    No customers yet
                @endif
            </p>
        </div>
    </div>
</div>
