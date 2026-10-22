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

<div class="invoice-form-layout">
    <div class="invoice-form-main">
        {{-- ============ 01 · Invoice details ============ --}}
        <section class="section-card">
            <div class="section-head">
                <div class="section-head-text">
                    <span class="section-num">01</span>
                    <div>
                        <h4 class="section-title">Invoice details</h4>
                        <p class="section-subtitle">Set the customer, currency and invoice schedule.</p>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="customer_id" class="form-label">Customer <span class="star">*</span></label>
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
                    <div class="field-help">Choose who this invoice is billed to.</div>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <label class="form-label">Currency <span class="star">*</span></label>
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
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label for="status" class="form-label">Status <span class="star">*</span></label>
                    <select name="status" id="status" class="form-select" required>
                        @foreach (\App\Models\Invoice::FORM_STATUSES as $formStatus)
                            <option value="{{ $formStatus }}" {{ old('status', $invoice->status ?? 'sent' ) === $formStatus ? 'selected' : '' }}>
                                {{ \App\Models\Invoice::FORM_STATUS_LABELS[$formStatus] }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <label for="invoice_date" class="form-label">Invoice date <span class="star">*</span></label>
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
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-6">
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
        </section>

        {{-- ============ 02 · Line items ============ --}}
        <section class="section-card">
            <div class="section-head">
                <div class="section-head-text">
                    <span class="section-num">02</span>
                    <div>
                        <h4 class="section-title">Line items <span class="star">*</span></h4>
                        <p class="section-subtitle">Add the products or services included in this invoice.</p>
                    </div>
                </div>
                <button type="button" class="btn-add-item" id="add-item">
                    <i class="bi bi-plus-lg"></i> Add item
                </button>
            </div>

            @error('items')
                <div class="field-error mb-2">{{ $message }}</div>
            @enderror

            <div id="items-body">
                @foreach ($itemRows as $index => $item)
                    <div class="item-panel item-row">
                        <div class="item-panel-head">
                            <div class="item-panel-head-left">
                                <span class="item-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="item-title">Invoice item</span>
                            </div>
                            <button
                                type="button"
                                class="item-remove remove-item"
                                title="Remove item"
                                aria-label="Remove item"
                            >
                                <i class="mdi mdi-delete-outline"></i>
                            </button>
                        </div>
                        <div class="item-grid">
                            <div>
                                <label class="field-label">Description</label>
                                <input
                                    type="text"
                                    name="items[{{ $index }}][item_description]"
                                    class="form-control item-description @error('items.'.$index.'.item_description') is-invalid @enderror"
                                    value="{{ old('items.'.$index.'.item_description', $item['item_description'] ?? '') }}"
                                    placeholder="Item description"
                                >
                                @error('items.'.$index.'.item_description')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">Quantity</label>
                                <input
                                    type="number"
                                    name="items[{{ $index }}][quantity]"
                                    class="form-control item-quantity @error('items.'.$index.'.quantity') is-invalid @enderror"
                                    value="{{ $item['quantity'] ?? '' }}"
                                    min="0"
                                    step="0.01"
                                    placeholder="1.00"
                                >
                                @error('items.'.$index.'.quantity')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">Unit price</label>
                                <div class="unit-price-wrap">
                                    <span class="currency-prefix">$</span>
                                    <input
                                        type="number"
                                        name="items[{{ $index }}][unit_price]"
                                        class="form-control item-unit-price @error('items.'.$index.'.unit_price') is-invalid @enderror"
                                        value="{{ $item['unit_price'] ?? '' }}"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                    >
                                </div>
                                @error('items.'.$index.'.unit_price')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">Discount type</label>
                                <select name="items[{{ $index }}][discount_type]" class="form-select item-discount-type">
                                    @foreach (['none' => 'None', 'flat' => 'Flat', 'percentage' => 'Percentage'] as $key => $label)
                                        <option value="{{ $key }}" {{ ($item['discount_type'] ?? 'none') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('items.'.$index.'.discount_type')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">Discount value</label>
                                <input
                                    type="number"
                                    name="items[{{ $index }}][discount_value]"
                                    class="form-control item-discount-value @error('items.'.$index.'.discount_value') is-invalid @enderror"
                                    value="{{ $item['discount_value'] ?? '' }}"
                                    {{ (($item['discount_type'] ?? 'none') === 'none') ? 'disabled' : '' }}
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                >
                                @error('items.'.$index.'.discount_value')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">&nbsp;</label>
                                <div class="line-total-box">
                                    <span class="line-total-label">Line total</span>
                                    <span class="item-line-total">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ============ 03 · Terms & conditions ============ --}}
        <section class="section-card">
            <div class="section-head">
                <div class="section-head-text">
                    <span class="section-num">03</span>
                    <div>
                        <h4 class="section-title">Terms &amp; conditions</h4>
                        <p class="section-subtitle">Add payment instructions or other information.</p>
                    </div>
                </div>
            </div>
            <div class="mb-2">
                <textarea
                    name="terms_and_conditions"
                    id="terms_and_conditions"
                    rows="4"
                    class="form-control"
                    placeholder="Payment is due within 30 days of the invoice date. Thank you for your business."
                >{{ old('terms_and_conditions', $invoice->terms_and_conditions) }}</textarea>
            </div>
            <div class="field-help">These terms will appear at the bottom of the invoice.</div>
        </section>
    </div>

    {{-- ============ Sidebar ============ --}}
    <aside class="invoice-form-side">
        <section class="section-card">
            <h4 class="side-title">Tax</h4>
            <p class="section-subtitle">Apply tax to the invoice total.</p>
            <div class="side-divider"></div>
            <div class="mb-3">
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
            <div class="{{ $taxable ? '' : 'd-none' }}" id="tax-rate-field">
                <label for="tax-rate" class="form-label">Tax rate (%) <span class="star">*</span></label>
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
        </section>

        <section class="section-card">
            <h4 class="side-title">Invoice total</h4>
            <p class="section-subtitle">Calculated automatically.</p>
            <div class="side-divider"></div>
            <div id="invoice-totals">
                <div class="totals-row">
                    <span class="totals-label">Subtotal</span>
                    <span class="totals-value" id="invoice-subtotal">0.00</span>
                </div>
                <div class="totals-row">
                    <span class="totals-label">Discount total</span>
                    <span class="totals-value totals-discount" id="invoice-discount-total">– 0.00</span>
                </div>
                <div class="totals-row">
                    <span class="totals-label">Tax total</span>
                    <span class="totals-value" id="invoice-tax-total">0.00</span>
                </div>
            </div>
            <div class="totals-grand">
                <span>Grand total</span>
                <span class="totals-grand-value" id="invoice-grand-total">0.00</span>
            </div>
        </section>

        <div class="save-row">
            <a href="{{ route('invoices.index') }}" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-save-invoice">
                <i class="bi bi-save"></i> {{ $isEdit ? 'Save changes' : 'Save invoice' }}
            </button>
        </div>
    </aside>
</div>
