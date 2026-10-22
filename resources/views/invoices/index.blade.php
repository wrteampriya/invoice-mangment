@extends('layouts.app')

@section('title', 'Invoices - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.3/dist/bootstrap-table.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-table-admin.css') }}">
@endpush

@section('content')
<div class="page-header-ui">
    <div class="page-header-ui-main">
        <span class="page-header-ui-icon"><i class="bi bi-receipt"></i></span>
        <div>
            <h3 class="page-header-ui-title">Invoices</h3>
            <p class="page-header-ui-subtitle">Track billing, due dates and payment status in one place.</p>
        </div>
    </div>
    <a href="{{ route('invoices.create') }}" class="btn btn-ui-primary">
        <i class="bi bi-plus-lg"></i> Create invoice
    </a>
</div>

<div class="stats-card">
    <div class="stats-item">
        <span class="stats-item-icon"><i class="bi bi-receipt"></i></span>
        <div class="stats-item-text">
            <span class="stats-item-label">Total invoices</span>
            <span class="stats-item-value">{{ $stats['total'] }}</span>
        </div>
    </div>
    <div class="stats-item">
        <span class="stats-item-dot stats-dot-red"></span>
        <div class="stats-item-text">
            <span class="stats-item-label">Overdue</span>
            <span class="stats-item-value">{{ $stats['overdue'] }}</span>
        </div>
    </div>
    <div class="stats-item">
        <span class="stats-item-dot stats-dot-blue"></span>
        <div class="stats-item-text">
            <span class="stats-item-label">Sent</span>
            <span class="stats-item-value">{{ $stats['sent'] }}</span>
        </div>
    </div>
    <div class="stats-item">
        <span class="stats-item-dot stats-dot-green"></span>
        <div class="stats-item-text">
            <span class="stats-item-label">Paid</span>
            <span class="stats-item-value">{{ $stats['paid'] }}</span>
        </div>
    </div>
    <div class="stats-note">Billed across <strong>{{ $stats['currencies'] }} currencies</strong></div>
</div>

@if (session('status'))
    <div class="list-status"><i class="mdi mdi-check-circle-outline"></i> {{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="list-status list-status-error"><i class="mdi mdi-alert-circle-outline"></i> {{ session('error') }}</div>
@endif

<div class="card bt-admin-card">
    <div class="card-body">
        <div class="bt-card-head">
            <div class="bt-card-head-text">
                <h4 class="bt-card-head-title">All invoices</h4>
                <p class="bt-card-head-subtitle">Showing your most recently created invoices.</p>
            </div>
            <div class="bt-card-controls">
                <span class="bt-search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" class="bt-search" data-bt-search placeholder="Search invoices..." autocomplete="off">
                </span>
                <button type="button" class="bt-icon-btn" data-bt-refresh title="Refresh" aria-label="Refresh">
                    <i class="bi bi-arrow-repeat"></i>
                </button>
            </div>
        </div>
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
                data-icons-prefix="bi"
                data-icons='{"refresh":"bi-arrow-repeat"}'
                data-ajax="adminTableAjax"
                data-query-params="adminQueryParams"
                data-loading-template="adminLoadingTemplate"
                data-classes="table"
                data-thead-classes=""
            >
                <thead>
                    <tr>
                        <th data-field="invoice_number" data-sortable="true" data-align="left" data-formatter="invoiceNumberFormatter">Invoice No.</th>
                        <th data-field="customer_name" data-formatter="invoiceCustomerFormatter">Customer</th>
                        <th data-field="invoice_date" data-sortable="true">Invoice Date</th>
                        <th data-field="due_date_raw" data-sortable="false" data-formatter="invoiceDueDateFormatter">Due Date</th>
                        <th data-field="status" data-formatter="invoiceStatusFormatter">Status</th>
                        <th data-field="grand_total" data-sortable="true" data-formatter="invoiceAmountFormatter">Amount</th>
                        <th data-field="show_url" data-align="center" data-formatter="invoiceActionsFormatter">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
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
<script>
    window.adminExtraParams = function () {
        var search = document.querySelector('[data-bt-search]');
        return {
            search: search ? search.value : ''
        };
    };
</script>
@endpush
