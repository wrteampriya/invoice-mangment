@extends('layouts.app')

@section('title', $customer->display_name . ' - ' . config('app.name'))

@section('content')
<div class="page-header">
    <h3 class="page-title">
        {{ $customer->display_name }}
        @if ($customer->customer_type === 'business')
            <span class="badge bg-primary ms-2">Business</span>
        @else
            <span class="badge bg-secondary ms-2">Individual</span>
        @endif
    </h3>
    <div class="d-flex gap-2">
        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-gradient-primary">Edit</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Contact details</h4>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8">{{ $customer->name }}</dd>
                    @if ($customer->customer_type === 'business')
                        <dt class="col-sm-4">Company</dt>
                        <dd class="col-sm-8">{{ $customer->company_name }}</dd>
                    @endif
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $customer->email ?? '—' }}</dd>
                    <dt class="col-sm-4">Phone</dt>
                    <dd class="col-sm-8">{{ $customer->phone ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Billing address</h4>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Address</dt>
                    <dd class="col-sm-8">{{ $customer->address }}</dd>
                    <dt class="col-sm-4">City</dt>
                    <dd class="col-sm-8">{{ $customer->city }}</dd>
                    <dt class="col-sm-4">State</dt>
                    <dd class="col-sm-8">{{ $customer->state ?? '—' }}</dd>
                    <dt class="col-sm-4">Postal code</dt>
                    <dd class="col-sm-8">{{ $customer->postal_code ?? '—' }}</dd>
                    <dt class="col-sm-4">Country</dt>
                    <dd class="col-sm-8">{{ \App\Support\Countries::list()[$customer->country_code] ?? $customer->country_code }}</dd>
                    @if ($customer->customer_type === 'business')
                        <dt class="col-sm-4">Tax ID</dt>
                        <dd class="col-sm-8">
                            @if ($customer->tax_id_type || $customer->tax_id)
                                {{ $customer->tax_id_type ? $customer->tax_id_type.': ' : '' }}{{ $customer->tax_id ?? '—' }}
                            @else
                                —
                            @endif
                        </dd>
                    @endif
                </dl>
                <p class="mt-3 mb-0"><small class="text-muted">Created {{ $customer->created_at }} · Updated {{ $customer->updated_at }}</small></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Invoice history</h4>
                <p class="text-muted mb-0">No invoices yet.</p>
            </div>
        </div>
    </div>
</div>
@endsection
