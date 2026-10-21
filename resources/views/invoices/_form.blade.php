@php
    $taxTypeLabels = \App\Models\Invoice::TAX_TYPE_LABELS;
    $currencies = \App\Models\Invoice::CURRENCIES;
    $isEdit = $invoice->exists;
    $selectedTaxType = old('tax_type', $invoice->tax_type ?? \App\Models\Invoice::TAX_TYPE_NONE);
    $taxable = $selectedTaxType !== \App\Models\Invoice::TAX_TYPE_NONE;

    $itemRows = old('items');
    if ($itemRows !== null) {
        $itemRows = array_values((array) $itemRows);
    } elseif ($isEdit) {
        $itemRows = $invoice->items->map->only(['item_description', 'quantity', 'unit_price', 'discount_type', 'discount_value'])->all();
    }

    if (empty($itemRows)) {
        $itemRows = [[]];
    }

    $selectedCustomerId = old('customer_id', $invoice->customer_id);
@endphp

<div class="mb-4 d-flex justify-content-end">
    <div class="text-muted">
        <small>
            <i class="mdi mdi-information-outline"></i>
            Invoice number is generated automatically when you save.
        </small>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h4 class="card-title">Invoice details</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                <select
                    name="customer_id"
                    id="customer_id"
                    class="form-select @error('customer_id') is-invalid @enderror"
                    required
                >
                    <option value="" {{ $selectedCustomerId ? '' : 'selected' }} disabled>Select customer</option>
                    @foreach ($customers as $customerOption)
                        <option
                            value="{{ $customerOption->id }}"
                            data-country="{{ $customerOption->country_code }}"
                            {{ (string) $selectedCustomerId === (string) $customerOption->id ? 'selected' : '' }}
                        >{{ $customerOption->display_name }}{{ $customerOption->customer_type === 'business' && $customerOption->name ? ' (' . $customerOption->name . ')' : '' }}</option>
                    @endforeach
                </select>
                @error('customer_id')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Customer country</label>
                <input
                    type="text"
                    id="customer-country-display"
                    class="form-control text-uppercase"
                    value="{{ old('customer_country', $invoice->customer_country) }}"
                    readonly
                    placeholder="—"
                    aria-label="Customer country (auto-filled)"
                >
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Currency <span class="text-danger">*</span></label>
                <select name="currency" id="currency" class="form-select" required>
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency }}" {{ old('currency', $invoice->currency ?: $currencies[0]) === $currency ? 'selected' : '' }}>{{ $currency }}</option>
                    @endforeach
                </select>
                @error('currency')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Invoice number</label>
                <input
                    type="text"
                    name="invoice_number"
                    id="invoice-number"
                    class="form-control"
                    value="{{ $isEdit ? $invoice->invoice_number : $invoiceNumber }}"
                    readonly
                >
            </div>
            <div class="col-md-4 mb-3">
                <label for="invoice_date" class="form-label">Invoice date <span class="text-danger">*</span></label>
                <input
                    type="date"
                    name="invoice_date"
                    id="invoice_date"
                    value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d') ?? now()->toDateString()) }}"
                    class="form-control @error('invoice_date') is-invalid @enderror"
                    required
                >
                @error('invoice_date')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="due_date" class="form-label">Due date</label>
                <input
                    type="date"
                    name="due_date"
                    id="due_date"
                    value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}"
                    class="form-control @error('due_date') is-invalid @enderror"
                >
                @error('due_date')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h4 class="card-title">Tax</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="tax_type" class="form-label">Tax type</label>
                <select name="tax_type" id="tax-type" class="form-select">
                    @foreach ($taxTypeLabels as $key => $label)
                        <option value="{{ $key }}" {{ $selectedTaxType === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('tax_type')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3 {{ $taxable ? '' : 'd-none' }}" id="tax-rate-field">
                <label for="tax-rate" class="form-label">Tax rate (%) <span class="text-danger">*</span></label>
                <input
                    type="number"
                    name="tax_rate"
                    id="tax-rate"
                    value="{{ old('tax_rate', $invoice->tax_rate) }}"
                    class="form-control @error('tax_rate') is-invalid @enderror"
                    min="0"
                    max="100"
                    step="0.01"
                    {{ $taxable ? '' : 'disabled' }}
                >
                @error('tax_rate')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="card-title mb-0">Line items <span class="text-danger">*</span></h4>
            <button type="button" class="btn btn-sm btn-gradient-primary" id="add-item">
                <i class="mdi mdi-plus"></i> Add item
            </button>
        </div>

        @error('items')
            <div class="field-error mb-2">{{ $message }}</div>
        @enderror

        <div class="table-responsive">
            <table class="table align-middle mb-1" id="items-table">
                <thead>
                    <tr>
                        <th style="min-width: 220px;">Description</th>
                        <th style="width: 110px;" class="text-center">Quantity</th>
                        <th style="width: 140px;" class="text-center">Unit price</th>
                        <th style="width: 150px;" class="text-center">Discount type</th>
                        <th style="width: 130px;" class="text-center">Discount value</th>
                        <th style="width: 130px;" class="text-end">Line total</th>
                        <th style="width: 60px;"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                    @foreach ($itemRows as $index => $item)
                        <tr class="item-row">
                            <td>
                                <textarea
                                    name="items[{{ $index }}][item_description]"
                                    rows="2"
                                    class="form-control item-description @error('items.'.$index.'.item_description') is-invalid @enderror"
                                    placeholder="Item description"
                                >{{ old('items.'.$index.'.item_description', $item['item_description'] ?? '') }}</textarea>
                                @error('items.'.$index.'.item_description')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="items[{{ $index }}][quantity]"
                                    class="form-control text-center item-quantity @error('items.'.$index.'.quantity') is-invalid @enderror"
                                    value="{{ $item['quantity'] ?? '' }}"
                                    min="0"
                                    step="0.01"
                                    placeholder="1.00"
                                >
                                @error('items.'.$index.'.quantity')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="items[{{ $index }}][unit_price]"
                                    class="form-control text-center item-unit-price @error('items.'.$index.'.unit_price') is-invalid @enderror"
                                    value="{{ $item['unit_price'] ?? '' }}"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                >
                                @error('items.'.$index.'.unit_price')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <select name="items[{{ $index }}][discount_type]" class="form-select item-discount-type">
                                    @foreach (['none' => 'None', 'flat' => 'Flat', 'percentage' => 'Percentage'] as $key => $label)
                                        <option value="{{ $key }}" {{ ($item['discount_type'] ?? 'none') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('items.'.$index.'.discount_type')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="items[{{ $index }}][discount_value]"
                                    class="form-control text-center item-discount-value @error('items.'.$index.'.discount_value') is-invalid @enderror"
                                    value="{{ $item['discount_value'] ?? '' }}"
                                    {{ (($item['discount_type'] ?? 'none') === 'none') ? 'disabled' : '' }}
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                >
                                @error('items.'.$index.'.discount_value')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </td>
                            <td class="text-end">
                                <span class="item-line-total fw-semibold">0.00</span>
                            </td>
                            <td class="text-center">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger remove-item"
                                    title="Remove item"
                                    aria-label="Remove item"
                                >
                                    <i class="mdi mdi-close"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h4 class="card-title">Totals</h4>
        <dl class="row mb-0" id="invoice-totals">
            <dt class="col-sm-6">Subtotal</dt>
            <dd class="col-sm-6 text-end" id="invoice-subtotal">0.00</dd>
            <dt class="col-sm-6">Discount total</dt>
            <dd class="col-sm-6 text-end" id="invoice-discount-total">0.00</dd>
            <dt class="col-sm-6">Tax total</dt>
            <dd class="col-sm-6 text-end" id="invoice-tax-total">0.00</dd>
            <dt class="col-sm-6">Grand total</dt>
            <dd class="col-sm-6 text-end fw-bold" id="invoice-grand-total">0.00</dd>
        </dl>
        <p class="mb-0 text-muted"><small>Totals are calculated automatically.</small></p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h4 class="card-title">Terms &amp; conditions</h4>
        <textarea
            name="terms_and_conditions"
            id="terms_and_conditions"
            rows="4"
            class="form-control"
            placeholder="Payment instructions, bank details, other terms…"
        >{{ old('terms_and_conditions', $invoice->terms_and_conditions) }}</textarea>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-gradient-primary">
        <i class="mdi mdi-content-save me-1"></i> {{ $isEdit ? 'Save Changes' : 'Save Invoice' }}
    </button>
</div>
