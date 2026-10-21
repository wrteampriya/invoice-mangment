@extends('layouts.app')

@section('title', $customer->display_name . ' - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/customer-show.css') }}">
@endpush

@php
    $initials = collect(explode(' ', trim((string) $customer->display_name)))
        ->filter()
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

@section('content')
<div class="customer-detail">
    {{-- Page header --}}
    <div class="customer-header">
        <div class="avatar-wrap">
            <span class="customer-avatar">{{ $initials ?: '?' }}</span>
            <span class="avatar-presence" title="Active"></span>
        </div>
        <div class="customer-heading">
            <h2 class="customer-name">
                {{ $customer->display_name }}
                <span class="status-badge badge-paid">Active</span>
            </h2>
            <p class="customer-subline mb-0">
                {{ $customer->customer_type === 'business' ? 'Business customer' : 'Individual customer' }}
                <span class="dot-sep">·</span>
                Customer since {{ $customer->created_at->format('F Y') }}
            </p>
        </div>
        <div class="customer-actions">
            <a href="{{ route('customers.index') }}" class="btn-customer-back">
                <i class="mdi mdi-arrow-left" style="font-size: 18px"></i> Back
            </a>
            <a href="{{ route('customers.edit', $customer) }}" class="btn-customer-edit">
                <i class="mdi mdi-pencil-outline" style="font-size: 18px"></i> Edit customer
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="customer-stats">
        <div class="sv-card stat-card">
            <span class="stat-icon is-blue"><i class="mdi mdi-receipt-text-outline"></i></span>
            <div>
                <p class="stat-label">Total invoices</p>
                <p class="stat-value mb-0">{{ $totalInvoices }}</p>
            </div>
        </div>
        <div class="sv-card stat-card">
            <span class="stat-icon is-green"><i class="mdi mdi-currency-inr"></i></span>
            <div>
                <p class="stat-label">Outstanding balance</p>
                <p class="stat-value mb-0">
                    @forelse ($outstandingByCurrency as $currency => $amount)
                        <span class="stat-sub">{{ format_money($amount, $currency) }}</span>
                    @empty
                        {{ format_money(0, 'INR') }}
                    @endforelse
                </p>
            </div>
        </div>
        <div class="sv-card stat-card">
            <span class="stat-icon is-purple"><i class="mdi mdi-calendar-month-outline"></i></span>
            <div>
                <p class="stat-label">Last activity</p>
                <p class="stat-value mb-0">{{ format_date_short($lastActivity) }}</p>
            </div>
        </div>
    </div>

    {{-- Contact + address --}}
    <div class="detail-grid">
        <div class="sv-card">
            <div class="sv-card-body">
                <div class="section-head mb-3">
                    <div>
                        <p class="kicker mb-0">Personal</p>
                        <h4 class="section-title">Contact details</h4>
                    </div>
                    <span class="section-icon"><i class="mdi mdi-account-outline"></i></span>
                </div>

                <div class="contact-row">
                    <span class="contact-icon"><i class="mdi mdi-account-outline"></i></span>
                    <div>
                        <p class="contact-label">Full name</p>
                        <p class="contact-value mb-0">{{ $customer->name }}</p>
                    </div>
                </div>
                @if ($customer->customer_type === 'business')
                    <div class="contact-row">
                        <span class="contact-icon"><i class="mdi mdi-domain-outline"></i></span>
                        <div>
                            <p class="contact-label">Company</p>
                            <p class="contact-value mb-0">{{ $customer->company_name ?: '—' }}</p>
                        </div>
                    </div>
                @endif
                <div class="contact-row">
                    <span class="contact-icon"><i class="mdi mdi-email-outline"></i></span>
                    <div>
                        <p class="contact-label">Email address</p>
                        <p class="contact-value mb-0">
                            @if ($customer->email)
                                <a class="email-link" href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                            @else
                                —
                            @endif
                        </p>
                    </div>
                </div>
                <div class="contact-row">
                    <span class="contact-icon"><i class="mdi mdi-phone-outline"></i></span>
                    <div>
                        <p class="contact-label">Phone number</p>
                        <p class="contact-value mb-0">{{ $customer->phone ?: '—' }}</p>
                    </div>
                </div>
                @if ($customer->customer_type === 'business' && ($customer->tax_id || $customer->tax_id_type))
                    <div class="contact-row">
                        <span class="contact-icon"><i class="mdi mdi-file-certificate-outline"></i></span>
                        <div>
                            <p class="contact-label">{{ $customer->tax_id_type ?: 'Tax ID' }}</p>
                            <p class="contact-value mb-0">{{ $customer->tax_id ?: '—' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="sv-card">
            <div class="sv-card-body">
                <div class="section-head mb-3">
                    <div>
                        <p class="kicker mb-0">Location</p>
                        <h4 class="section-title">Billing address</h4>
                    </div>
                    <span class="section-icon is-green"><i class="mdi mdi-map-marker-outline"></i></span>
                </div>

                <div class="address-copy">
                    <p class="address-name">{{ $customer->company_name ?: $customer->name }}</p>
                    <p>{{ $customer->address }}</p>
                    <p>{{ collect([$customer->city, $customer->state, $customer->postal_code])->filter()->implode(', ') }}</p>
                    <p>{{ country_name($customer->country_code) }}</p>
                </div>

                <div class="address-meta">
                    <span class="meta-dot"></span>
                    Last updated {{ $customer->updated_at->format('F j, Y \a\t g:i A') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Invoice history --}}
    <div class="sv-card history-card">
        <div class="history-head">
            <div class="history-title">
                <div>
                    <p class="kicker mb-0">Billing activity</p>
                    <h4 class="section-title">Invoice history</h4>
                    <p class="history-sub">All invoices created for this customer.</p>
                </div>
                <a href="{{ route('invoices.create') }}" class="btn-create-invoice">
                    <i class="mdi mdi-plus" style="font-size: 18px"></i> Create invoice
                </a>
            </div>
        </div>

        @if ($invoices->isEmpty())
            <div class="empty-state">
                <span class="empty-icon"><i class="mdi mdi-receipt-text-outline"></i></span>
                <p class="empty-title">No invoices yet</p>
                <p class="empty-text">Create your first invoice for {{ $customer->display_name }} and it will appear here.</p>
            </div>
        @else
            <div class="invoice-table-wrap">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="num">Total</th>
                            <th class="num">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td><a class="inv-number" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                <td>{{ format_date_short($invoice->invoice_date) }}</td>
                                <td>{{ format_date_short($invoice->due_date) }}</td>
                                <td><span class="status-badge badge-{{ $invoice->display_status }}">{{ $invoice->display_status }}</span></td>
                                <td class="num text-strong">{{ format_money($invoice->grand_total, $invoice->currency) }}</td>
                                <td class="num text-strong" style="color: {{ $invoice->balance > 0 ? '#d14343' : '#1e9e5a' }}">
                                    {{ format_money($invoice->balance, $invoice->currency) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
