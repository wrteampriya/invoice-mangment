{{-- Latest 5 invoices with quick view / PDF actions --}}
<div class="dash-card recent-card">
    <div class="dash-card-head">
        <div>
            <h4 class="dash-card-title">Recent invoices</h4>
            <p class="dash-card-sub mb-0">Latest {{ $recentInvoices->count() }} {{ $recentInvoices->count() === 1 ? 'invoice' : 'invoices' }}</p>
        </div>
        <a href="{{ route('invoices.index') }}" class="link-view-all">View all invoices</a>
    </div>

    @if ($recentInvoices->isEmpty())
        <div class="empty-state">
            <span class="empty-icon"><i class="mdi mdi-receipt-text-outline"></i></span>
            <p class="empty-title mb-1">No invoices yet</p>
            <p class="empty-text mb-0">Create your first invoice and it will appear here.</p>
        </div>
    @else
        <div class="recent-table-wrap">
            <table class="recent-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Currency</th>
                        <th class="num">Grand Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentInvoices as $invoice)
                        <tr>
                            <td>
                                <a href="{{ $invoice->view_url }}" class="inv-number">{{ $invoice->invoice_number }}</a>
                            </td>
                            <td class="cell-customer">{{ $invoice->customer?->display_name ?? '—' }}</td>
                            <td>{{ format_date_short($invoice->invoice_date) }}</td>
                            <td>{{ format_date_short($invoice->due_date) }}</td>
                            <td><span class="status-badge badge-{{ $invoice->display_status }}">{{ $invoice->display_status }}</span></td>
                            <td><span class="currency-chip">{{ $invoice->currency }}</span></td>
                            <td class="num cell-strong">{{ format_money($invoice->grand_total, $invoice->currency) }}</td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ $invoice->view_url }}" class="row-action"><i class="mdi mdi-eye-outline"></i> View</a>
                                    <a href="{{ $invoice->pdf_url }}" class="row-action" download><i class="mdi mdi-download"></i> PDF</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
