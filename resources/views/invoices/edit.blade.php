@extends('layouts.app')

@section('title', 'Edit Invoice ' . $invoice->invoice_number . ' - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/invoice-form.css') }}">
@endpush

@section('content')
<div class="page-header-inv">
    <div class="page-header-inv-main">
        <span class="page-header-inv-icon"><i class="bi bi-pencil-square"></i></span>
        <div>
            <div class="page-header-inv-eyebrow">Edit invoice</div>
            <h3 class="page-header-inv-title">Edit invoice <span class="text-muted">{{ $invoice->invoice_number }}</span></h3>
            <p class="page-header-inv-subtitle">Update this invoice's customer, line items and billing details.</p>
        </div>
    </div>
    <span class="auto-chip"><i class="bi bi-stars"></i> Totals update automatically</span>
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
