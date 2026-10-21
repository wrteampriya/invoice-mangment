@extends('layouts.app')

@section('title', 'Edit Invoice ' . $invoice->invoice_number . ' - ' . config('app.name'))

@section('content')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi mdi-pencil-box-outline"></i>
        </span> Edit Invoice <span class="text-muted">{{ $invoice->invoice_number }}</span>
    </h3>
    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary">
        <i class="mdi mdi-arrow-left me-1"></i> Back
    </a>
</div>

<form method="POST" action="{{ route('invoices.update', $invoice) }}" id="invoice-form">
    @csrf
    @method('PUT')
    @include('invoices._form')
</form>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/invoice-form.js') }}"></script>
@endpush
