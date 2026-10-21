@extends('layouts.app')

@section('title', 'Invoices - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.3/dist/bootstrap-table.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-table-admin.css') }}">
@endpush

@section('content')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi mdi-receipt"></i>
        </span> Invoices
    </h3>
    <a href="{{ route('invoices.create') }}" class="btn btn-sm btn-gradient-primary">
        <i class="mdi mdi-plus"></i> Create Invoice
    </a>
</div>

@if (session('status'))
    <div class="list-status"><i class="mdi mdi-check-circle-outline"></i> {{ session('status') }}</div>
@endif

<div class="card bt-admin-card">
    <div class="card-body">
        <div class="table-responsive">
            <table
                id="invoices-table"
                data-toggle="table"
                data-url="{{ route('invoices.index', ['format' => 'json']) }}"
                data-method="get"
                data-side-pagination="server"
                data-sort-name="id"
                data-sort-order="desc"
                data-sort-stable="true"
                data-pagination="true"
                data-page-size="10"
                data-page-list="[10, 25, 50, 100]"
                data-search="true"
                data-search-align="left"
                data-buttons-align="right"
                data-show-refresh="true"
                data-show-columns="true"
                data-icons-prefix="bi"
                data-icons='{"refresh":"bi-arrow-repeat","columns":"bi-table"}'
                data-ajax="adminTableAjax"
                data-query-params="adminQueryParams"
                data-loading-template="adminLoadingTemplate"
                data-classes="table table-striped"
                data-thead-classes=""
            >
                <thead>
                    <tr>
                        <th data-field="id" data-sortable="true" data-align="left">ID</th>
                        <th data-field="invoice_number" data-sortable="true" data-formatter="invoiceNumberFormatter">Invoice No.</th>
                        <th data-field="customer_name" data-formatter="invoiceCustomerFormatter">Customer</th>
                        <th data-field="invoice_date" data-sortable="true">Invoice Date</th>
                        <th data-field="due_date_raw" data-sortable="false" data-formatter="invoiceDueDateFormatter">Due Date</th>
                        <th data-field="grand_total" data-sortable="true" data-formatter="invoiceAmountFormatter">Amount</th>
                        <th data-field="show_url" data-align="center" data-formatter="invoiceActionsFormatter">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if (isset($invoices) && $invoices->isNotEmpty())
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->id }}</td>
                                <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->customer?->display_name ?? '—' }}</td>
                                <td>{{ $invoice->invoice_date?->format('d M Y') }}</td>
                                <td>{{ $invoice->due_date?->format('d M Y') ?? '—' }}</td>
                                <td class="fw-semibold">{{ $invoice->formatMoney((float) $invoice->grand_total) }}</td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="table-action-btn table-action-view" title="View" aria-label="View {{ $invoice->invoice_number }}">
                                            <i class="mdi mdi-eye-outline"></i>
                                        </a>
                                        <a href="{{ route('invoices.edit', $invoice) }}" class="table-action-btn table-action-edit" title="Edit" aria-label="Edit {{ $invoice->invoice_number }}">
                                            <i class="mdi mdi-pencil-outline"></i>
                                        </a>
                                        <button
                                            type="button"
                                            class="table-action-btn table-action-delete"
                                            title="Delete"
                                            aria-label="Delete {{ $invoice->invoice_number }}"
                                            data-delete-url="{{ route('invoices.destroy', $invoice) }}"
                                            data-delete-name="{{ $invoice->invoice_number }}"
                                        >
                                            <i class="mdi mdi-delete-outline"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.3/dist/bootstrap-table.min.js"></script>
<script src="{{ asset('assets/js/bootstrap-table-admin.js') }}"></script>
@endpush
