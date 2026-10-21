<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #1f2430; font-size: 12px; margin: 28px 34px; }
        .muted { color: #6b7280; }
        .head { width: 100%; border-bottom: 3px solid #2f56d6; padding-bottom: 14px; margin-bottom: 18px; }
        .head td { vertical-align: top; }
        h1 { font-size: 22px; margin: 0 0 2px; color: #2f56d6; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; background: #eef0f4; color: #5f6b7a; }
        .badge.paid { background: #e1f7ec; color: #1e9e5a; }
        .badge.partial { background: #e7eeff; color: #435abe; }
        .badge.overdue { background: #fdecea; color: #d14343; }
        .badge.cancelled { background: #3f4654; color: #e9ecf3; }
        .badge.draft { background: #f4f5f7; color: #9aa2b1; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th { background: #f2f5fb; text-align: left; font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: #556077; padding: 8px 10px; border-bottom: 1px solid #dfe3ec; }
        table.items td { padding: 8px 10px; border-bottom: 1px solid #eef1f6; }
        table.items td.num, table.items th.num { text-align: right; }
        table.meta { width: 42%; }
        table.meta td { padding: 2px 0; font-size: 11px; }
        table.meta td.label { color: #6b7280; }
        table.meta td.val { text-align: right; font-weight: bold; }
        table.totals { min-width: 240px; }
        table.totals td { padding: 4px 0; font-size: 12px; }
        table.totals td.label { color: #6b7280; border-bottom: 1px solid #f1f3f8; }
        table.totals td.val { text-align: right; font-weight: bold; border-bottom: 1px solid #f1f3f8; }
        table.totals tr.grand td { font-size: 14px; color: #2f56d6; border-bottom: none; border-top: 2px solid #2f56d6; }
        .section-title { font-size: 12px; font-weight: bold; letter-spacing: .1em; text-transform: uppercase; color: #556077; margin: 22px 0 6px; }
        .terms { font-size: 11px; color: #4a5057; }
        .footer { margin-top: 26px; border-top: 1px solid #e9ecf3; padding-top: 10px; font-size: 10px; color: #8b93a7; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <h1>{{ strtoupper($invoice->invoice_number) }}</h1>
                <span class="muted">Issued on {{ $invoice->invoice_date?->format('M j, Y') }}</span>
                @if ($invoice->due_date)
                    <span class="muted"> · Due {{ $invoice->due_date->format('M j, Y') }}</span>
                @endif
                <div style="margin-top:6px;">
                    <span class="badge {{ $invoice->display_status }}">{{ $invoice->display_status }}</span>
                </div>
            </td>
            <td align="right">
                <strong style="font-size:14px;">{{ $invoice->user->name }}</strong><br>
                <span class="muted">{{ $invoice->user->email }}</span><br>
                <span class="muted">{{ \App\Support\Countries::list()[$invoice->user->country ?? null] ?? 'Invoice Manager' }}</span>
            </td>
        </tr>
    </table>

    <table width="100%">
        <tr>
            <td width="48%" style="vertical-align:top;">
                <span class="section-title">Billed to</span>
                <strong>{{ $invoice->customer?->display_name }}</strong><br>
                <span class="muted">{{ $invoice->customer?->customer_type === 'business' ? 'Business' : 'Individual' }}</span><br>
                @if ($invoice->customer?->email)<span class="muted">{{ $invoice->customer->email }}</span><br>@endif
                @if ($invoice->customer)
                    <span class="muted">
                        {{ $invoice->customer->address }}<br>
                        {{ collect([$invoice->customer->city, $invoice->customer->state, $invoice->customer->postal_code])->filter()->implode(', ') }}<br>
                        {{ $invoice->customer_country }}
                    </span>
                @endif
            </td>
            <td style="vertical-align:top;" align="right">
                <table class="meta" align="right">
                    <tr><td class="label">Invoice number</td><td class="val">{{ $invoice->invoice_number }}</td></tr>
                    <tr><td class="label">Issue date</td><td class="val">{{ $invoice->invoice_date?->format('M j, Y') }}</td></tr>
                    <tr><td class="label">Due date</td><td class="val">{{ $invoice->due_date?->format('M j, Y') ?? '—' }}</td></tr>
                    <tr><td class="label">Tax type</td><td class="val">{{ $invoice->tax_type ? \App\Models\Invoice::TAX_TYPE_LABELS[$invoice->tax_type] : 'None' }}</td></tr>
                    <tr><td class="label">Balance due</td><td class="val">{{ format_money($invoice->balance, $invoice->currency) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <span class="section-title">Items</span>
    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">Qty</th>
                <th class="num">Price</th>
                <th class="num">Discount</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoice->items as $item)
                <tr>
                    <td>{{ $item->item_description }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ format_money($item->unit_price, $invoice->currency) }}</td>
                    <td class="num">
                        @if ($item->discount_type === 'percentage')
                            -{{ $item->discount_value }}%
                        @elseif ($item->discount_type === 'flat')
                            -{{ format_money($item->discount_value, $invoice->currency) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="num"><strong>{{ format_money($item->line_total, $invoice->currency) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="5">No items on this invoice.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table align="right" class="totals" style="margin-top:10px;">
        <tr><td class="label">Subtotal</td><td class="val">{{ format_money($invoice->subtotal, $invoice->currency) }}</td></tr>
        <tr><td class="label">Discount</td><td class="val">{{ format_money($invoice->discount_total, $invoice->currency) }}</td></tr>
        <tr><td class="label">
                Tax{{ $invoice->tax_rate !== null ? ' (' . $invoice->tax_rate . '%)' : '' }}
            </td>
            <td class="val">{{ format_money($invoice->tax_total, $invoice->currency) }}</td></tr>
        <tr class="grand"><td class="label">Grand total</td><td class="val">{{ format_money($invoice->grand_total, $invoice->currency) }}</td></tr>
        @if ($invoice->paid_total > 0)
            <tr><td class="label">Paid</td><td class="val" style="color:#1e9e5a;">{{ format_money($invoice->paid_total, $invoice->currency) }}</td></tr>
            <tr><td class="label">Balance</td><td class="val" style="color:#d14343;">{{ format_money($invoice->balance, $invoice->currency) }}</td></tr>
        @endif
    </table>
    <div style="clear:both;"></div>

    @if ($invoice->payments->isNotEmpty())
        <span class="section-title">Payments received</span>
        <table class="items">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date?->format('M j, Y') }}</td>
                        <td>{{ $payment->methodLabel() }}</td>
                        <td>{{ $payment->reference ?? '—' }}</td>
                        <td class="num">{{ format_money($payment->amount, $invoice->currency) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <span class="section-title">Terms &amp; conditions</span>
    <p class="terms">{{ $invoice->terms_and_conditions ?: 'No terms specified.' }}</p>

    <div class="footer">
        {{ $invoice->invoice_number }} · Issued by {{ $invoice->user->name }} · Generated on {{ now()->format('M j, Y \a\t g:i A') }}
    </div>
</body>
</html>
