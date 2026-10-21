<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Create the invoice and its lines inside a transaction. All totals are
     * recalculated server-side; nothing is trusted from the request payload.
     */
    public function createInvoice(int $userId, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($userId, $data, $items) {
            $built = $this->buildInvoiceData($userId, $data, $items);

            $invoice = Invoice::create($built['invoice']);
            $invoice->items()->createMany($built['items']);

            return $invoice->fresh('items');
        });
    }

    /**
     * Replace the invoice's lines and recalculate, inside a transaction.
     * The invoice number and user are never changed on update.
     */
    public function updateInvoice(Invoice $invoice, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $items) {
            $built = $this->buildInvoiceData($invoice->user_id, $data, $items, $invoice->invoice_number);
            $built['invoice']['invoice_number'] = $invoice->invoice_number;

            $invoice->update($built['invoice']);
            $invoice->items()->delete();
            $invoice->items()->createMany($built['items']);

            return $invoice->fresh('items');
        });
    }

    /**
     * Next sequential invoice number for a user, e.g. "INV-0001".
     */
    public function nextInvoiceNumber(int $userId): string
    {
        $lastSequence = (int) Invoice::query()
            ->where('user_id', $userId)
            ->where('invoice_number', 'like', 'INV-%')
            ->get(['invoice_number'])
            ->map(fn ($invoice) => (int) substr($invoice->invoice_number, strlen('INV-')))
            ->max();

        return 'INV-' . str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Assemble the invoice record (with recalculated totals) and its
     * persistable lines from validated input. customer_country is always
     * snapshotted from the customer record, user_id always from the caller.
     */
    protected function buildInvoiceData(int $userId, array $data, array $items, ?string $invoiceNumber = null): array
    {
        $customer = Customer::query()
            ->where('user_id', $userId)
            ->findOrFail($data['customer_id']);

        $lines = $this->calculateItems($items);
        $totals = $this->calculateTotals($lines, $data['tax_type'] ?? null, $data['tax_rate'] ?? null);

        return [
            'invoice' => [
                'user_id' => $userId,
                'customer_id' => $customer->id,
                'invoice_number' => $invoiceNumber ?? $this->nextInvoiceNumber($userId),
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'currency' => $data['currency'],
                'customer_country' => strtoupper($customer->country_code),
                'tax_type' => $totals['tax_type'],
                'tax_rate' => $totals['tax_rate'],
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
            ],
            'items' => $this->persistableItems($lines),
        ];
    }

    /**
     * Per-line calculation (each step rounded to 2 decimals):
     * gross = quantity x unit_price
     * discount = flat: value | percentage: gross x value / 100
     * line_total = gross - discount
     */
    protected function calculateItems(array $input): array
    {
        $lines = [];

        foreach ($input as $row) {
            $quantity = (float) $row['quantity'];
            $unitPrice = (float) $row['unit_price'];
            $gross = $this->round2($quantity * $unitPrice);

            $discountType = $row['discount_type'] ?? InvoiceItem::DISCOUNT_TYPE_NONE;
            $discountValue = isset($row['discount_value']) && $discountType !== InvoiceItem::DISCOUNT_TYPE_NONE
                ? (float) $row['discount_value']
                : null;

            $discount = match ($discountType) {
                'flat' => $this->round2($discountValue ?? 0),
                'percentage' => $this->round2(($discountValue ?? 0) * $gross / 100),
                default => 0.0,
            };

            $lines[] = [
                'item_description' => trim((string) $row['item_description']),
                'quantity' => $this->round2($quantity),
                'unit_price' => $this->round2($unitPrice),
                'discount_type' => $discountType,
                'discount_value' => $discountValue === null ? null : $this->round2($discountValue),
                'line_total' => $this->round2($gross - $discount),
                '_discount' => $discount,
                '_gross' => $gross,
            ];
        }

        return $lines;
    }

    /**
     * Header totals (each step rounded to 2 decimals):
     * subtotal = sum of line gross
     * discount_total = sum of line discounts
     * tax_total = (subtotal - discount_total) x tax_rate / 100  (0 without tax)
     * grand_total = subtotal - discount_total + tax_total
     */
    protected function calculateTotals(array $lines, ?string $taxType, float|string|null $taxRate): array
    {
        [$taxType, $taxRate] = $this->normalizeTax($taxType, $taxRate);

        $subtotal = $this->round2(array_sum(array_column($lines, '_gross')));
        $discountTotal = $this->round2(array_sum(array_column($lines, '_discount')));
        $taxTotal = $taxRate === null
            ? 0.0
            : $this->round2(($subtotal - $discountTotal) * $taxRate / 100);
        $grandTotal = $this->round2($subtotal - $discountTotal + $taxTotal);

        return [
            'tax_type' => $taxType,
            'tax_rate' => $taxRate,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * Tax type "none" (or missing) always stores null type + null rate.
     */
    protected function normalizeTax(?string $taxType, float|string|null $taxRate): array
    {
        if ($taxType === null || $taxType === Invoice::TAX_TYPE_NONE) {
            return [null, null];
        }

        return [$taxType, $taxRate !== null ? (float) $taxRate : null];
    }

    /**
     * Strip internal calculation keys (prefixed with "_") used while summing.
     */
    protected function persistableItems(array $lines): array
    {
        return array_map(
            fn (array $line) => array_filter($line, fn ($key) => $key[0] !== '_', ARRAY_FILTER_USE_KEY),
            $lines
        );
    }

    /**
     * Round half up to 2 decimals, matching the client-side calculation.
     */
    protected function round2(float $value): float
    {
        return round($value, 2, PHP_ROUND_HALF_UP);
    }
}
