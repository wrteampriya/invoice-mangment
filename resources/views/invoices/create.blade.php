@extends('layouts.app')

@section('title', 'Create Invoice - ' . config('app.name'))

@section('content')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi mdi-file-plus-outline"></i>
        </span> Create Invoice
    </h3>
    <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="mdi mdi-arrow-left me-1"></i> Back
    </a>
</div>

<form method="POST" action="{{ route('invoices.store') }}" id="invoice-form">
    @csrf
    @include('invoices._form')
</form>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/invoice-form.js') }}"></script>
@endpush
