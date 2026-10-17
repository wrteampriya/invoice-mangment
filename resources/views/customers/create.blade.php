@extends('layouts.app')

@section('title', 'Add Customer - ' . config('app.name'))

@section('content')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi mdi-account-plus"></i>
        </span> Add Customer
    </h3>
    <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="mdi mdi-arrow-left me-1"></i> Back
    </a>
</div>

<form method="POST" action="{{ route('customers.store') }}">
    @csrf
    @include('customers._form')
</form>
@endsection
