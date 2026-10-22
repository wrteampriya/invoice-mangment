<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice Statement</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #1f2430; font-size: 12px; margin: 28px 34px; }
        .muted { color: #6b7280; }
        h1 { font-size: 21px; margin: 0 0 2px; color: #2f56d6; }
        .head { width: 100%; border-bottom: 3px solid #2f56d6; padding-bottom: 12px; margin-bottom: 16px; }
        .head td { vertical-align: bottom; }
        .currency-head { margin: 20px 0 4px; font-size: 15px; font-weight: bold; color: #1f2430; }
        .currency-sub { font-size: 11px; color: #6b7280; margin: 0 0 8px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { background: #f2f5fb; text-align: left; font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: #556077; padding: 7px 10px; border-bottom: 1px solid #dfe3ec; }
        table.items td { padding: 7px 10px; border-bottom: 1px solid #eef1f6; font-size: 11.5px; }
        table.items td.num, table.items th.num { text-align: right; }
        tr.group-sum td { font-weight: bold; background: #f8f9fd; border-top: 2px solid #2f56d6; border-bottom: none; }
        .due-now { color: #d14343; font-weight: bold; }
        .paid-now { color: #1e9e5a; }
        .footer { margin-top: 24px; border-top: 1px solid #e9ecf3; padding-top: 10px; font-size: 10px; color: #8b93a7; }
        break-paying { }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <h1>Invoice Statement</h1>
                <span class="muted">All invoices and payments issued through {{ config('app.name') }}.</span>
            </td>
            <td align="right">
                <strong>{{ $user->name }}</strong><br>
                <span class="muted">{{ $user->email }}</span><br>
                <span class="muted">Generated on {{ $generatedAt->format('M j, Y \a\t g:i A') }}</span>
            </td>
        </tr>
    </table>

    @forelse ($currencyGroups as $currency => $group)
        <p class="currency-head">{{ $currency }}</p>
        <p class="currency-sub">
            {{ $group['invoices']->count() }} invoice{{ $group['invoices']->count() === 1 ? '' : 's' }} ·
            Grand total {{ format_money_pdf($group['grandTotal'], $currency) }} ·
            Paid {{ format_money_pdf($group['paidTotal'], $currency) }} ·
            Outstanding {{ format_money_pdf($group['balanceTotal'], $currency) }}
        </p>

        <table class="items">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th class="num">Total</th>
                    <th class="num">Paid</th>
                    <th class="num">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['invoices'] as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->customer?->display_name ?? '—' }}</td>
                        <td>{{ $invoice->invoice_date?->format('M j, Y') }}</td>
                        <td>{{ $invoice->due_date?->format('M j, Y') ?? '—' }}</td>
                        <td>{{ ucfirst($invoice->display_status) }}</td>
                        <td class="num">{{ format_money_pdf($invoice->grand_total, $currency) }}</td>
                        <td class="num paid-now">{{ format_money_pdf($invoice->paid_total, $currency) }}</td>
                        <td class="num {{ $invoice->balance > 0 ? 'due-now' : '' }}">{{ format_money_pdf($invoice->balance, $currency) }}</td>
                    </tr>
                @endforeach
                <tr class="group-sum">
                    <td colspan="5">Total ({{ $currency }})</td>
                    <td class="num">{{ format_money_pdf($group['grandTotal'], $currency) }}</td>
                    <td class="num">{{ format_money_pdf($group['paidTotal'], $currency) }}</td>
                    <td class="num">{{ format_money_pdf($group['balanceTotal'], $currency) }}</td>
                </tr>
            </tbody>
        </table>

        @if ($group['payments']->isNotEmpty())
            <span class="section-title" style="display:block;font-size:11px;font-weight:bold;letter-spacing:.1em;text-transform:uppercase;color:#556077;margin:14px 0 4px;">Payments ({{ $currency }})</span>
            <table class="items">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th class="num">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['payments'] as $payment)
                        <tr>
                            <td>{{ $payment->payment_date?->format('M j, Y') }}</td>
                            <td>{{ $payment->invoice_number }}</td>
                            <td>{{ $payment->methodLabel() }}</td>
                            <td>{{ $payment->reference ?? '—' }}</td>
                            <td class="num">{{ format_money_pdf($payment->amount, $currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @empty
        <p>No invoices have been created yet.</p>
    @endforelse

    <div class="footer">
        {{ config('app.name') }} · Statement generated on {{ $generatedAt->format('M j, Y \a\t g:i A') }}
    </div>
</body>
</html>
