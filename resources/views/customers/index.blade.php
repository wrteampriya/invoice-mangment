@extends('layouts.app')

@section('title', 'Customers - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.3/dist/bootstrap-table.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-table-admin.css') }}">
@endpush

@section('content')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi mdi-contacts"></i>
        </span> Customers
    </h3>
    <a href="{{ route('customers.create') }}" class="btn btn-sm btn-gradient-primary">
        <i class="mdi mdi-plus"></i> Add Customer
    </a>
</div>

@if (session('status'))
    <div class="list-status"><i class="mdi mdi-check-circle-outline"></i> {{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="list-status list-status-error"><i class="mdi mdi-alert-circle-outline"></i> {{ session('error') }}</div>
@endif

<div class="card bt-admin-card">
    <div class="card-body">
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
                data-search="true"
                data-search-align="right"
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
                        <th data-field="raw_name" data-sortable="true" data-formatter="customerNameFormatter">Name</th>
                        <th data-field="customer_type" data-sortable="true" data-formatter="customerTypeFormatter">Type</th>
                        <th data-field="email" data-sortable="true" data-formatter="customerTextFormatter">Email</th>
                        <th data-field="phone" data-sortable="true" data-formatter="customerTextFormatter">Phone</th>
                        <th data-field="city_country" data-sortable="true" data-formatter="customerTextFormatter">City / Country</th>
                        <th data-field="show_url" data-align="center" data-formatter="customerActionsFormatter">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if (isset($customers) && $customers->isNotEmpty())
                        @foreach ($customers as $customer)
                            <tr>
                                <td>{{ $customer->id }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $customer->display_name }}</div>
                                    @if ($customer->customer_type === 'business' && $customer->name)
                                        <small class="text-muted">{{ $customer->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($customer->customer_type === 'business')
                                        <span class="badge-pill-type badge-type-business">Business</span>
                                    @else
                                        <span class="badge-pill-type badge-type-individual">Individual</span>
                                    @endif
                                </td>
                                <td>{{ $customer->email ?? '—' }}</td>
                                <td>{{ $customer->phone ?? '—' }}</td>
                                <td>{{ $customer->city ? $customer->city . ($customer->country_code ? ', ' . $customer->country_code : '') : ($customer->country_code ?? '—') }}</td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('customers.show', $customer) }}" class="table-action-btn table-action-view" title="View" aria-label="View {{ $customer->display_name }}">
                                            <i class="mdi mdi-eye-outline"></i>
                                        </a>
                                        <a href="{{ route('customers.edit', $customer) }}" class="table-action-btn table-action-edit" title="Edit" aria-label="Edit {{ $customer->display_name }}">
                                            <i class="mdi mdi-pencil-outline"></i>
                                        </a>
                                        <button
                                            type="button"
                                            class="table-action-btn table-action-delete"
                                            title="Delete"
                                            aria-label="Delete {{ $customer->display_name }}"
                                            data-delete-url="{{ route('customers.destroy', $customer) }}"
                                            data-delete-name="{{ $customer->display_name }}"
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
    'title' => 'Delete Customer',
    'message' => 'Delete <strong id="deleteRecordName"></strong>? This cannot be undone.',
    'button' => 'Delete',
])
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.3/dist/bootstrap-table.min.js"></script>
<script src="{{ asset('assets/js/bootstrap-table-admin.js') }}"></script>
@endpush
