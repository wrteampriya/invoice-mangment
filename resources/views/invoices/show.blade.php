@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number . ' - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/invoice-show.css') }}">
@endpush

@php
    $customer   = $invoice->customer;
    $initials   = collect(preg_split('/\s+/', trim((string) $customer?->display_name)))
        ->filter()
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->take(2)
        ->implode('');
    $taxLabel   = $invoice->tax_type
        ? ((\App\Models\Invoice::TAX_TYPE_LABELS[$invoice->tax_type] ?? $invoice->tax_type)
           . ($invoice->tax_rate !== null ? ' ' . $invoice->tax_rate . '%' : ''))
        : 'None';
    $countryName = \App\Support\Countries::list()[$invoice->customer_country] ?? $invoice->customer_country;
@endphp

@section('content')
<div class="invoice-show">

    {{-- ── Page header ── --}}
    <div class="inv-page-header">
        <div class="inv-page-header-left">
            <p class="inv-kicker">
                <i class="mdi mdi-receipt-text-outline"></i> Invoice details
            </p>
            <h1>Invoice {{ $invoice->invoice_number }}</h1>
            <p class="inv-subtitle">Review invoice information, customer details and line items.</p>
        </div>
        <div class="inv-header-actions">
            <a href="{{ route('invoices.index') }}" class="btn-inv-back">
                <i class="mdi mdi-arrow-left"></i> Back
            </a>
            <a href="{{ route('invoices.edit', $invoice) }}" class="btn-inv-edit">
                <i class="mdi mdi-pencil-outline"></i> Edit
            </a>
            <button
                type="button"
                class="btn-inv-delete"
                data-delete-url="{{ route('invoices.destroy', $invoice) }}"
                data-delete-name="{{ $invoice->invoice_number }}"
            >
                <i class="mdi mdi-delete-outline"></i> Delete
            </button>
        </div>
    </div>

    @if (session('status'))
        <div class="list-status"><i class="mdi mdi-check-circle-outline"></i> {{ session('status') }}</div>
    @endif

    {{-- ── Top info row: invoice details + billed to ── --}}
    <div class="inv-info-row">

        {{-- Left: invoice number, grand total, dates --}}
        <div class="inv-card inv-info-card">
            <p class="inv-number-label">Invoice Number</p>
            <div class="inv-number-row">
                <p class="inv-number-value">{{ $invoice->invoice_number }}</p>
                <span class="inv-currency-badge">{{ $invoice->currency }} invoice</span>
            </div>

            <div class="inv-grand-total-block">
                <p class="inv-grand-label">Grand total</p>
                <p class="inv-grand-amount">{{ $invoice->formatMoney((float) $invoice->grand_total) }}</p>
                @if ((float) $invoice->tax_total > 0)
                    <p class="inv-grand-tax">Including {{ $invoice->formatMoney((float) $invoice->tax_total) }} {{ $invoice->tax_type ? strtoupper(\App\Models\Invoice::TAX_TYPE_LABELS[$invoice->tax_type] ?? $invoice->tax_type) : '' }}</p>
                @endif
            </div>

            <div class="inv-dates">
                <div class="inv-date-item">
                    <span class="inv-date-icon"><i class="mdi mdi-calendar-outline"></i></span>
                    <div>
                        <p class="inv-date-label">Issue date</p>
                        <p class="inv-date-value">{{ $invoice->invoice_date?->format('d M Y') ?? '—' }}</p>
                    </div>
                </div>
                @if ($invoice->due_date)
                    <div class="inv-date-item">
                        <span class="inv-date-icon"><i class="mdi mdi-calendar-clock-outline"></i></span>
                        <div>
                            <p class="inv-date-label">Due date</p>
                            <p class="inv-date-value">{{ $invoice->due_date->format('d M Y') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: billed to --}}
        <div class="inv-card inv-billed-card">
            <div class="inv-billed-head">
                <div>
                    <p class="inv-billed-kicker">Billed to</p>
                    <h4 class="inv-billed-title">Customer</h4>
                </div>
                @if ($customer)
                    <a href="{{ route('customers.show', $customer) }}" class="inv-view-profile">View profile →</a>
                @endif
            </div>

            @if ($customer)
                <div class="inv-customer-row">
                    <span class="inv-customer-avatar">{{ $initials ?: '?' }}</span>
                    <div>
                        <p class="inv-customer-name">{{ $customer->display_name }}</p>
                        <p class="inv-customer-type">
                            {{ $customer->customer_type === 'business' ? 'Business customer' : 'Individual customer' }}
                        </p>
                    </div>
                </div>

                @if ($customer->email)
                    <div class="inv-contact-row">
                        <span class="inv-contact-icon"><i class="mdi mdi-email-outline"></i></span>
                        <div>
                            <p class="inv-contact-label">Email address</p>
                            <p class="inv-contact-value">{{ $customer->email }}</p>
                        </div>
                    </div>
                @endif

                @if ($countryName)
                    <div class="inv-contact-row">
                        <span class="inv-contact-icon"><i class="mdi mdi-map-marker-outline"></i></span>
                        <div>
                            <p class="inv-contact-label">Billing country</p>
                            <p class="inv-contact-value">{{ $countryName }}</p>
                        </div>
                    </div>
                @endif
            @else
                <p class="text-muted">—</p>
            @endif
        </div>
    </div>

    {{-- ── Invoice breakdown / items ── --}}
    <div class="inv-card inv-breakdown-card">
        <div class="inv-breakdown-head">
            <div>
                <p class="inv-breakdown-kicker">Invoice Breakdown</p>
                <h4 class="inv-breakdown-title">Items</h4>
            </div>
            <span class="inv-item-count">{{ $invoice->items->count() }} {{ Str::plural('item', $invoice->items->count()) }}</span>
        </div>

        <div class="table-responsive">
            <table class="inv-items-table">
                <thead>
                    <tr>
                        <th style="width:40%">Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Discount</th>
                        <th class="text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->items as $index => $item)
                        <tr>
                            <td>
                                @php
                                    $descLines = array_filter(array_map('trim', explode("\n", (string) $item->item_description)));
                                    $mainDesc  = array_shift($descLines) ?? '';
                                    $subDesc   = implode(' ', $descLines);
                                @endphp
                                <span class="inv-item-num">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <p class="inv-item-desc d-inline ms-1">{{ $mainDesc }}</p>
                                @if ($subDesc)
                                    <p class="inv-item-subdesc" style="margin-left:42px;">{{ $subDesc }}</p>
                                @endif
                            </td>
                            <td class="font-mono">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td class="font-mono">{{ $invoice->formatMoney((float) $item->unit_price) }}</td>
                            <td class="font-mono">
                                @if ($item->discount_type === 'flat')
                                    {{ $invoice->formatMoney((float) $item->discount_value) }}
                                @elseif ($item->discount_type === 'percentage')
                                    {{ $item->discount_value }}%
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-right font-mono inv-line-total">{{ $invoice->formatMoney((float) $item->line_total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted" style="padding: 32px 24px;">No items added.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Bottom row: terms + totals ── --}}
    <div class="inv-bottom-row">

        {{-- Terms & conditions --}}
        <div class="inv-card inv-terms-card">
            <p class="inv-terms-kicker">Additional Information</p>
            <h4 class="inv-terms-title">Terms &amp; conditions</h4>
            <hr class="inv-terms-divider">
            <p class="inv-terms-body">{{ $invoice->terms_and_conditions ?? '—' }}</p>
        </div>

        {{-- Totals --}}
        <div class="inv-card inv-totals-card">
            <div class="inv-totals-row">
                <span class="inv-totals-label">Subtotal</span>
                <span class="inv-totals-val">{{ $invoice->formatMoney((float) $invoice->subtotal) }}</span>
            </div>
            @if ((float) $invoice->discount_total > 0)
                <div class="inv-totals-row is-discount">
                    <span class="inv-totals-label">Discount total</span>
                    <span class="inv-totals-val">- {{ $invoice->formatMoney((float) $invoice->discount_total) }}</span>
                </div>
            @endif
            <div class="inv-totals-row">
                <span class="inv-totals-label">
                    Tax <span class="inv-totals-tax-sub">{{ $taxLabel }}</span>
                </span>
                <span class="inv-totals-val">{{ $invoice->formatMoney((float) $invoice->tax_total) }}</span>
            </div>
            <div class="inv-totals-row is-grand">
                <span class="inv-totals-label">Grand total</span>
                <span class="inv-totals-val">{{ $invoice->formatMoney((float) $invoice->grand_total) }}</span>
            </div>
        </div>
    </div>

</div>

@include('partials.delete-modal', [
    'title'   => 'Delete Invoice',
    'message' => 'Delete invoice <strong id="deleteRecordName"></strong>? This cannot be undone.',
    'button'  => 'Delete',
])
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/bootstrap-table-admin.js') }}"></script>
@endpush
