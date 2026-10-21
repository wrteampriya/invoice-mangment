@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number . ' - ' . config('app.name'))

@section('content')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi mdi-receipt"></i>
        </span> Invoice <span class="fw-bold">{{ $invoice->invoice_number }}</span>
    </h3>
    <div class="d-flex gap-2">
        <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-primary">
            <i class="mdi mdi-pencil-outline me-1"></i> Edit
        </a>
        <button
            type="button"
            class="btn btn-sm btn-gradient-danger"
            data-delete-url="{{ route('invoices.destroy', $invoice) }}"
            data-delete-name="{{ $invoice->invoice_number }}"
        >
            <i class="mdi mdi-delete-outline me-1"></i> Delete
        </button>
    </div>
</div>

@if (session('status'))
    <div class="list-status"><i class="mdi mdi-check-circle-outline"></i> {{ session('status') }}</div>
@endif

<div class="row">
    <div class="col-md-8 stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <h4 class="card-title mb-2">{{ $invoice->invoice_number }}</h4>
                        <div class="text-muted">{{ $invoice->invoice_date?->format('d M Y') }}
                            @if ($invoice->due_date)
                                · Due {{ $invoice->due_date->format('d M Y') }}
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted">Grand total</div>
                        <div class="h4 font-weight-bold mb-0">{{ $invoice->formatMoney((float) $invoice->grand_total) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Customer</h4>
                <div class="fw-semibold">{{ $invoice->customer?->display_name }}</div>
                <div class="text-muted">
                    @if ($invoice->customer?->customer_type === 'business' && $invoice->customer?->name)
                        {{ $invoice->customer->name }}<br>
                    @endif
                    {{ $invoice->customer?->email ?? '—' }}<br>
                    {{ \App\Support\Countries::list()[$invoice->customer_country] ?? $invoice->customer_country }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card stretch-card mt-4">
    <div class="card-body">
        <h4 class="card-title">Items</h4>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-center">Unit price</th>
                        <th class="text-center">Discount</th>
                        <th class="text-end">Line total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->item_description }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-center">{{ $invoice->formatMoney((float) $item->unit_price) }}</td>
                            <td class="text-center">
                                @if ($item->discount_type === 'flat')
                                    {{ $invoice->formatMoney((float) $item->discount_value) }}
                                @elseif ($item->discount_type === 'percentage')
                                    {{ $item->discount_value }}%
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">{{ $invoice->formatMoney((float) $item->line_total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center">No items.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4 stretch-card">
        <div class="card">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-7">Subtotal</dt>
                    <dd class="col-5 text-end mb-2">{{ $invoice->formatMoney((float) $invoice->subtotal) }}</dd>
                    <dt class="col-7">Discount total</dt>
                    <dd class="col-5 text-end mb-2">- {{ $invoice->formatMoney((float) $invoice->discount_total) }}</dd>
                    <dt class="col-7">Tax ({{ $invoice->tax_type ? (\App\Models\Invoice::TAX_TYPE_LABELS[$invoice->tax_type] ?? $invoice->tax_type) : 'None' }} {{ $invoice->tax_rate !== null ? $invoice->tax_rate . '%' : '' }})</dt>
                    <dd class="col-5 text-end mb-2">{{ $invoice->formatMoney((float) $invoice->tax_total) }}</dd>
                    <dt class="col-7 font-weight-bold">Grand total</dt>
                    <dd class="col-5 text-end mb-0 font-weight-bold">{{ $invoice->formatMoney((float) $invoice->grand_total) }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-8 stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Terms &amp; conditions</h4>
                <p class="mb-0" style="white-space: pre-line;">{{ $invoice->terms_and_conditions ?? '—' }}</p>
            </div>
        </div>
    </div>
</div>

@include('partials.delete-modal', [
    'title' => 'Delete Invoice',
    'message' => 'Delete invoice <strong id="deleteRecordName"></strong>? This cannot be undone.',
    'button' => 'Delete',
])
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/bootstrap-table-admin.js') }}"></script>
@endpush
