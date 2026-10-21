/**
 * Invoice create/edit form behaviour:
 * - dynamic line item rows (add / remove / reindex, at least one row)
 * - exact same calculation rules as the backend (round each step)
 * - live totals formatted with the selected currency
 * - tax type None hides + disables the rate input
 * - customer selection auto-fills the read-only country and suggests
 *   GST 18% for India (IN) while staying editable
 */
(function () {
    'use strict';

    var form = document.getElementById('invoice-form');
    if (!form) {
        return;
    }

    var itemsBody = document.getElementById('items-body');
    var customerSelect = document.getElementById('customer_id');
    var countryDisplay = document.getElementById('customer-country-display');
    var currencySelect = document.getElementById('currency');
    var taxTypeSelect = document.getElementById('tax-type');
    var taxRateField = document.getElementById('tax-rate-field');
    var taxRateInput = document.getElementById('tax-rate');
    var minimumRows = 1;

    //============================== overall wiring ==============================
    form.addEventListener('input', function (event) {
        if (event.target.closest('.item-row') || event.target.id === 'tax-rate' || event.target.id === 'tax-type') {
            recalcAll();
        }
    });

    form.addEventListener('change', function (event) {
        var target = event.target;

        if (target.classList.contains('item-discount-type')) {
            var row = target.closest('.item-row');
            refreshDiscountState(row);
            recalcAll();
        }

        if (target.id === 'tax-type') {
            refreshTaxVisibility();
            recalcAll();
        }

        if (target.id === 'currency') {
            recalcAll();
        }
    });

    form.addEventListener('click', function (event) {
        var addBtn = event.target.closest('#add-item');
        if (addBtn) {
            addItemRow();
            return;
        }

        var removeBtn = event.target.closest('.remove-item');
        if (removeBtn) {
            removeItemRow(removeBtn.closest('.item-row'));
        }
    });

    customerSelect.addEventListener('change', onCustomerChange);

    refreshTaxVisibility();
    refreshAllDiscountStates();
    if (customerSelect.value) {
        onCustomerChange();
    }
    recalcAll();

    //============================== line items ==============================
    function addItemRow() {
        var clone = itemsBody.lastElementChild.cloneNode(true);
        ['item-description', 'item-quantity', 'item-unit-price', 'item-discount-value'].forEach(function (cls) {
            var input = clone.querySelector('.' + cls);
            if (input) {
                input.value = '';
                input.classList.remove('is-invalid');
                var error = input.parentNode?.querySelector('.field-error');
                if (error) {
                    error.remove();
                }
            }
        });
        clone.querySelector('.item-discount-type').value = 'none';
        clone.querySelector('.item-line-total').textContent = '0.00';
        itemsBody.appendChild(clone);

        refreshDiscountState(clone);
        refreshRemoveButtons();
        reindexRows();
        recalcAll();
    }

    function removeItemRow(row) {
        if (!row || itemsBody.children.length <= minimumRows) {
            return;
        }
        row.remove();
        refreshRemoveButtons();
        reindexRows();
        recalcAll();
    }

    function refreshRemoveButtons() {
        var removable = itemsBody.children.length > minimumRows;
        Array.prototype.forEach.call(itemsBody.querySelectorAll('.remove-item'), function (button) {
            button.disabled = !removable;
        });
    }

    /**
     * Field names must match the server's items[0][...] collection indexes.
     */
    function reindexRows() {
        Array.prototype.forEach.call(itemsBody.querySelectorAll('.item-row'), function (row, index) {
            row.querySelectorAll('input, textarea, select').forEach(function (field) {
                var name = field.getAttribute('name');
                if (!name) {
                    return;
                }
                field.name = name.replace(/^items\[\d+\]/, 'items[' + index + ']');
            });
        });
    }

    //============================== calculations ==============================
    function round2(value) {
        return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
    }

    function num(value) {
        var parsed = parseFloat(value);
        return isNaN(parsed) ? 0 : parsed;
    }

    function recalcRowFor(row) {
        if (!row) {
            return;
        }

        var quantity = num(row.querySelector('.item-quantity')?.value);
        var unitPrice = num(row.querySelector('.item-unit-price')?.value);
        var gross = round2(quantity * unitPrice);

        var discountType = row.querySelector('.item-discount-type')?.value || 'none';
        var discountValue = num(row.querySelector('.item-discount-value')?.value);

        var discount = 0;
        if (discountType === 'flat') {
            discount = round2(discountValue);
        } else if (discountType === 'percentage') {
            discount = round2(gross * discountValue / 100);
        }

        row.querySelector('.item-line-total').textContent = formatCurrency(round2(gross - discount));
    }

    function recalcAll() {
        Array.prototype.forEach.call(itemsBody.querySelectorAll('.item-row'), recalcRowFor);
        recalcTotals();
    }

    function recalcTotals() {
        var subtotal = 0;
        var discountTotal = 0;

        Array.prototype.forEach.call(itemsBody.querySelectorAll('.item-row'), function (row) {
            var quantity = num(row.querySelector('.item-quantity')?.value);
            var unitPrice = num(row.querySelector('.item-unit-price')?.value);
            var gross = round2(quantity * unitPrice);
            subtotal = round2(subtotal + gross);

            var discountType = row.querySelector('.item-discount-type')?.value || 'none';
            var discountValue = num(row.querySelector('.item-discount-value')?.value);
            if (discountType === 'flat') {
                discountTotal = round2(discountTotal + round2(discountValue));
            } else if (discountType === 'percentage') {
                discountTotal = round2(discountTotal + round2(gross * discountValue / 100));
            }
        });

        // 0 when tax type is None; the rate stays editable otherwise
        var netTotal = round2(subtotal - discountTotal);
        var taxRate = taxRateInput && !taxRateInput.disabled ? num(taxRateInput.value) : 0;
        var taxTotal = taxRate ? round2(netTotal * taxRate / 100) : 0;
        var grandTotal = round2(netTotal + taxTotal);

        setText('invoice-subtotal', formatCurrency(subtotal));
        setText('invoice-discount-total', formatCurrency(discountTotal));
        setText('invoice-tax-total', formatCurrency(taxTotal));
        setText('invoice-grand-total', formatCurrency(grandTotal));
    }

    function setText(elementId, text) {
        var el = document.getElementById(elementId);
        if (el) {
            el.textContent = text;
        }
    }

    function formatCurrency(value) {
        var currency = (currencySelect?.value || 'USD').toUpperCase();
        var formatted;
        try {
            formatted = new Intl.NumberFormat(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value);
        } catch (e) {
            formatted = round2(value).toFixed(2);
        }
        return currency + ' ' + formatted;
    }

    //============================== field state ==============================
    /**
     * Tax type None: hide + disable the rate input so it is not submitted.
     */
    function refreshTaxVisibility() {
        var taxVisible = taxTypeSelect.value !== 'none';
        taxRateField.classList.toggle('d-none', !taxVisible);
        taxRateInput.disabled = !taxVisible;
        if (!taxVisible) {
            taxRateInput.value = '';
        }
    }

    function refreshAllDiscountStates() {
        Array.prototype.forEach.call(itemsBody.querySelectorAll('.item-row'), refreshDiscountState);
        refreshRemoveButtons();
    }

    /**
     * A discount type of None disables its value input (it is not submitted).
     */
    function refreshDiscountState(row) {
        if (!row) {
            return;
        }
        var type = row.querySelector('.item-discount-type')?.value || 'none';
        var input = row.querySelector('.item-discount-value');
        var enabled = type !== 'none';
        input.disabled = !enabled;
        if (!enabled) {
            input.value = '';
        }
    }

    //============================== customer / country / GST ==============================
    function onCustomerChange() {
        var selected = customerSelect.selectedOptions[0];
        if (!selected) {
            return;
        }

        var country = selected.getAttribute('data-country') || '';
        if (countryDisplay) {
            countryDisplay.value = country.toUpperCase();
        }

        // Suggest GST 18% for Indian customers, still editable
        if (country.toUpperCase() === 'IN') {
            taxTypeSelect.value = 'gst';
            if (!taxRateInput.value || !num(taxRateInput.value)) {
                taxRateInput.value = '18.00';
            }
            refreshTaxVisibility();
            recalcAll();
        }
    }
})();
