@extends('layouts.app')

@section('title', 'Edit Customer - ' . config('app.name'))

@section('content')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi mdi-account-edit"></i>
        </span> Edit Customer
    </h3>
    <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="mdi mdi-arrow-left me-1"></i> Back
    </a>
</div>

<form method="POST" action="{{ route('customers.update', $customer) }}">
    @csrf
    @method('PUT')
    @include('customers._form')
</form>
@endsection
