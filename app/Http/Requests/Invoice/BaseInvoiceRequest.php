<?php

namespace App\Http\Requests\Invoice;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\Currencies;
use Illuminate\Foundation\Http\FormRequest;

class BaseInvoiceRequest extends FormRequest
{
    /**
     * A customer_id that exists but does not belong to the current user is a
     * forbidden access attempt, not a validation error (403, per spec).
     */
    public function authorize(): bool
    {
        $customerId = $this->input('customer_id');

        if ($customerId !== null && ! Customer::query()
            ->where('user_id', (int) $this->user()->id)
            ->whereKey((int) $customerId)
            ->exists()) {
            return false;
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $input = [
            'tax_type' => $this->input('tax_type') !== null ? strtolower(trim((string) $this->input('tax_type'))) : null,
        ];

        if (array_key_exists('tax_rate', $this->all())) {
            $rate = trim((string) $this->input('tax_rate'));
            $input['tax_rate'] = $rate === '' ? null : str_replace(',', '', $rate);
        }

        if (is_array($this->input('items'))) {
            $items = $this->input('items');

            $input['items'] = array_map(function ($row) {
                if (! is_array($row)) {
                    return $row;
                }

                $row['item_description'] = trim((string) ($row['item_description'] ?? ''));
                $row['discount_type'] = isset($row['discount_type']) && $row['discount_type'] !== ''
                    ? strtolower(trim((string) $row['discount_type']))
                    : InvoiceItem::DISCOUNT_TYPE_NONE;

                if ($row['discount_type'] === InvoiceItem::DISCOUNT_TYPE_NONE) {
                    $row['discount_value'] = null;
                }

                return $row;
            }, $items);
        }

        $this->merge($input);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'currency' => ['required', 'string', 'max:10', 'in:'.implode(',', Currencies::codes())],
            'status' => ['nullable', 'string', 'in:'.implode(',', Invoice::FORM_STATUSES)],
            'tax_type' => ['required', 'in:'.implode(',', Invoice::TAX_TYPES)],
            /*
             * Rate rule per spec:
             * - type None: hidden, value stays null.
             * - GST/VAT/Sales Tax: required, 0..100.
             */
            'tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'required_if:tax_type,gst,vat,sales_tax',
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_description' => ['required', 'string', 'max:2000'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.discount_value' => [
                'nullable',
                'numeric',
                'min:0',
                'required_if:items.*.discount_type,flat,percentage',
            ],
            'terms_and_conditions' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function itemRows(): array
    {
        return array_values((array) $this->input('items', []));
    }

    /**
     * Cross-field item rules (percentage range, flat cap) are checked in the
     * validator's after() hook because they need sibling row values. The
     * due-date range is covered by after_or_equal:invoice_date.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->itemRows();

            foreach ($items as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $discountType = $row['discount_type'] ?? InvoiceItem::DISCOUNT_TYPE_NONE;
                $discountValue = $row['discount_value'] ?? null;

                if ($discountValue === null || ! is_numeric($discountValue)) {
                    continue;
                }

                $value = (float) $discountValue;

                if ($discountType === 'flat') {
                    $lineGross = (float) ($row['quantity'] ?? 0) * (float) ($row['unit_price'] ?? 0);
                    if ($value > $lineGross) {
                        $validator->errors()->add(
                            'items.'.$index.'.discount_value',
                            __('Flat discount cannot be greater than the line amount (:amount).', ['amount' => number_format($lineGross, 2)])
                        );
                    }
                }

                if ($discountType === 'percentage' && $value > 100) {
                    $validator->errors()->add(
                        'items.'.$index.'.discount_value',
                        __('Percentage discount cannot be greater than 100.')
                    );
                }
            }
        });
    }
}
