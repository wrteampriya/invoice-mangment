{{-- Top 5 customers by total invoiced --}}
<div class="dash-card top-card">
    <div class="dash-card-head">
        <div>
            <h4 class="dash-card-title">Top customers</h4>
            <p class="dash-card-sub mb-0">By total invoiced</p>
        </div>
        <a href="{{ route('customers.index') }}" class="link-view-all">View all</a>
    </div>

    @if ($topCustomers->isEmpty())
        <div class="top-empty">
            <p class="empty-text mb-0">No invoices yet.</p>
        </div>
    @else
        <div class="top-list">
            @foreach ($topCustomers as $customer)
                <a href="{{ $customer['showUrl'] }}" class="top-row">
                    <span class="top-avatar av-{{ $loop->index % 4 }}">{{ $customer['initials'] }}</span>
                    <div class="top-info">
                        <p class="top-name mb-0">{{ $customer['name'] }}</p>
                        <p class="top-meta mb-0">
                            @if ($customer['country'])
                                <span class="country-chip">{{ $customer['country'] }}</span>
                            @endif
                            <span class="text-muted">{{ $customer['invoiceCount'] }} invoice{{ $customer['invoiceCount'] === 1 ? '' : 's' }}</span>
                        </p>
                    </div>
                    <span class="top-amount">{{ format_money($customer['amount'], $customer['currency']) }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
