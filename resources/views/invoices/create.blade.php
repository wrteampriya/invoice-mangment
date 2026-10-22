@extends('layouts.app')

@section('title', 'Create Invoice - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/invoice-form.css') }}">
@endpush

@section('content')
    <div class="page-header-inv">
        <div class="page-header-inv-main">
            <span class="page-header-inv-icon"><i class="bi bi-receipt"></i></span>
            <div>
                <div class="page-header-inv-eyebrow">New invoice</div>
                <h3 class="page-header-inv-title">Create invoice</h3>
                <p class="page-header-inv-subtitle">Add your customer, invoice details and billable items.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('invoices.store') }}" id="invoice-form">
        @csrf
        @include('invoices._form')
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/invoice-form.js') }}"></script>
@endpush
