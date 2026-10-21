@extends('layouts.app')

@section('title', 'Dashboard - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard.css') }}">
@endpush

@section('content')
<div class="dashboard-page">

    @include('dashboard.partials.header')

    @include('dashboard.partials.stat-cards')

    <div class="dash-mid">
        <div class="dash-chart-col">
            @include('dashboard.partials.revenue-chart')
        </div>
        <div class="dash-top-col">
            @include('dashboard.partials.top-customers')
        </div>
    </div>

    @include('dashboard.partials.recent-invoices')

</div>
@endsection
