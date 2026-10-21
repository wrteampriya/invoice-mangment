{{-- Page header: OVERVIEW kicker, title, subtitle and quick actions --}}
<div class="dash-header">
    <div>
        <p class="dash-kicker mb-0">Overview</p>
        <h2 class="dash-title">Dashboard</h2>
        <p class="dash-sub mb-0">Your invoices, revenue and customers at a glance.</p>
    </div>
    <div class="dash-actions">
        <a href="{{ route('dashboard.statement') }}" class="btn-dash-ghost" title="Download a PDF statement of all invoices and payments">
            <i class="mdi mdi-download" style="font-size: 18px"></i> Download Statement
        </a>
        <a href="{{ route('customers.create') }}" class="btn-dash-ghost">
            <i class="mdi mdi-account-plus-outline" style="font-size: 18px"></i> New customer
        </a>
        <a href="{{ route('invoices.create') }}" class="btn-dash-primary">
            <i class="mdi mdi-plus" style="font-size: 20px"></i> Create Invoice
        </a>
    </div>
</div>
