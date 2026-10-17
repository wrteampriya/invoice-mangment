@extends('layouts.app')

@section('title', 'Customers - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.3/dist/bootstrap-table.min.css">
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

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table
                id="customers-table"
                data-toggle="table"
                data-url="{{ route('customers.index', ['format' => 'json']) }}"
                data-pagination="true"
                data-search="true"
                data-show-columns="true"
                data-show-refresh="true"
                data-page-size="10"
                data-page-list="[10, 25, 50, 100, all]"
                data-classes="table table-hover"
                data-thead-classes="table-light"
            >
                <thead>
                    <tr>
                        <th data-field="id" data-sortable="true">ID</th>
                        <th data-field="name" data-sortable="true">Name</th>
                        <th data-field="customer_type" data-sortable="true">Type</th>
                        <th data-field="email" data-sortable="true">Email</th>
                        <th data-field="phone">Phone</th>
                        <th data-field="city_country" data-sortable="true">City / Country</th>
                        <th data-field="actions" data-align="right">Actions</th>
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
                                        <span class="badge bg-primary">Business</span>
                                    @else
                                        <span class="badge bg-secondary">Individual</span>
                                    @endif
                                </td>
                                <td>{{ $customer->email ?? '—' }}</td>
                                <td>{{ $customer->phone ?? '—' }}</td>
                                <td>{{ $customer->city ? $customer->city . ($customer->country_code ? ', ' . $customer->country_code : '') : ($customer->country_code ?? '—') }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-primary" title="View" aria-label="View {{ $customer->display_name }}">
                                            <i class="mdi mdi-eye-outline"></i>
                                        </a>
                                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-secondary" title="Edit" aria-label="Edit {{ $customer->display_name }}">
                                            <i class="mdi mdi-pencil-outline"></i>
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
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

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Delete <strong id="deleteCustomerName"></strong>? This cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteCustomerForm" method="POST" action="#">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-gradient-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.3/dist/bootstrap-table.min.js"></script>
<script>
(function () {
    var modalEl = document.getElementById('deleteModal');
    var form = document.getElementById('deleteCustomerForm');
    var nameEl = document.getElementById('deleteCustomerName');

    function openModal() {
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'deleteModalBackdrop';
        backdrop.addEventListener('click', closeModal);
        document.body.appendChild(backdrop);
    }

    function closeModal() {
        if (window.bootstrap && window.bootstrap.Modal) {
            var instance = window.bootstrap.Modal.getInstance(modalEl);
            if (instance) {
                instance.hide();
                return;
            }
        }
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        var backdrop = document.getElementById('deleteModalBackdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }

    // Event delegation so dynamic table rows from bootstrap-table are handled
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-delete-url]');
        if (button) {
            form.action = button.getAttribute('data-delete-url');
            nameEl.textContent = button.getAttribute('data-delete-name');
            openModal();
        }
    });

    modalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modalEl.classList.contains('show')) {
            closeModal();
        }
    });
})();
</script>
@endpush
