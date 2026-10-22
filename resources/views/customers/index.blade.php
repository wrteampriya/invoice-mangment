@extends('layouts.app')

@section('title', 'Customers - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.3/dist/bootstrap-table.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-table-admin.css') }}">
@endpush

@section('content')
<div class="page-header-ui">
    <div class="page-header-ui-main">
        <span class="page-header-ui-icon"><i class="bi bi-people"></i></span>
        <div>
            <h3 class="page-header-ui-title">Customers</h3>
            <p class="page-header-ui-subtitle">Manage customer details and billing contacts in one place.</p>
        </div>
    </div>
    <a href="{{ route('customers.create') }}" class="btn btn-ui-primary">
        <i class="bi bi-plus-lg"></i> Add customer
    </a>
</div>

<div class="stats-card">
    <div class="stats-item">
        <span class="stats-item-icon"><i class="bi bi-people"></i></span>
        <div class="stats-item-text">
            <span class="stats-item-label">Total customers</span>
            <span class="stats-item-value">{{ $stats['total'] }}</span>
        </div>
    </div>
    <div class="stats-item">
        <span class="stats-item-dot stats-dot-blue"></span>
        <div class="stats-item-text">
            <span class="stats-item-label">Companies</span>
            <span class="stats-item-value">{{ $stats['business'] }}</span>
        </div>
    </div>
    <div class="stats-item">
        <span class="stats-item-dot stats-dot-gray"></span>
        <div class="stats-item-text">
            <span class="stats-item-label">Individuals</span>
            <span class="stats-item-value">{{ $stats['individual'] }}</span>
        </div>
    </div>
    <div class="stats-note">Customer records from <strong>{{ $stats['countries'] }} countries</strong></div>
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
                <h4 class="bt-card-head-title">All customers</h4>
                <p class="bt-card-head-subtitle">Showing your most recently added customers.</p>
            </div>
            <div class="bt-card-controls">
                <span class="bt-search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" class="bt-search" data-bt-search placeholder="Search customers..." autocomplete="off">
                </span>
                <span class="bt-select-wrap">
                    <i class="bi bi-funnel"></i>
                    <select class="bt-select" data-bt-filter aria-label="Filter by type">
                        <option value="">All</option>
                        <option value="business">Business</option>
                        <option value="individual">Individual</option>
                    </select>
                </span>
                <button type="button" class="bt-icon-btn" data-bt-refresh title="Refresh" aria-label="Refresh">
                    <i class="bi bi-arrow-repeat"></i>
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table
                id="customers-table"
                data-toggle="table"
                data-url="{{ route('customers.index', ['format' => 'json']) }}"
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
                        <th data-field="raw_name" data-sortable="true" data-formatter="customerNameFormatter">Customer</th>
                        <th data-field="customer_type" data-sortable="true" data-formatter="customerTypeFormatter">Type</th>
                        <th data-field="email" data-sortable="true" data-formatter="customerTextFormatter">Email address</th>
                        <th data-field="phone" data-sortable="true" data-formatter="customerTextFormatter">Phone</th>
                        <th data-field="city_country" data-sortable="true" data-formatter="customerLocationFormatter">Location</th>
                        <th data-field="show_url" data-align="center" data-formatter="customerActionsFormatter">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@include('partials.delete-modal', [
    'title' => 'Delete Customer',
    'message' => 'Delete <strong id="deleteRecordName"></strong>? This cannot be undone.',
    'button' => 'Delete',
])
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.3/dist/bootstrap-table.min.js"></script>
<script src="{{ asset('assets/js/bootstrap-table-admin.js') }}"></script>
<script>
    window.adminExtraParams = function () {
        var search = document.querySelector('[data-bt-search]');
        var filter = document.querySelector('[data-bt-filter]');
        return {
            search: search ? search.value : '',
            type: filter ? filter.value : ''
        };
    };
</script>
@endpush
