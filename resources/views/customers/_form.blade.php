@php
    $type = old('customer_type', $customer->customer_type ?? 'individual');
    $isBusiness = $type === 'business';
    $countries = \App\Support\Countries::forSelect();
    $taxTypes = \App\Models\Customer::TAX_ID_TYPES;
@endphp

<div class="mb-4">
    <div class="segmented" role="radiogroup" aria-label="Customer type">
        <input type="radio" name="customer_type" id="type_individual" value="individual" {{ $type === 'individual' ? 'checked' : '' }}>
        <label for="type_individual">Individual</label>
        <input type="radio" name="customer_type" id="type_business" value="business" {{ $type === 'business' ? 'checked' : '' }}>
        <label for="type_business">Business</label>
    </div>
    @error('customer_type')
        <div class="field-error">{{ $message }}</div>
    @enderror
</div>

<div class="card mb-4">
    <div class="card-body">
        <h4 class="card-title">Contact</h4>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="name" class="form-label"><span id="name-label-text">{{ $isBusiness ? 'Contact Name' : 'Full Name' }}</span> <span class="text-danger">*</span></label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $customer->name) }}"
                    class="form-control @error('name') is-invalid @enderror"
                    id="name"
                    required
                    autocomplete="name"
                >
                @error('name')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email', $customer->email) }}"
                    class="form-control @error('email') is-invalid @enderror"
                    id="email"
                    autocomplete="email"
                >
                @error('email')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input
                    type="tel"
                    name="phone"
                    value="{{ old('phone', $customer->phone) }}"
                    class="form-control @error('phone') is-invalid @enderror"
                    id="phone"
                    autocomplete="tel"
                >
                @error('phone')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 {{ $isBusiness ? '' : 'd-none' }}" id="business-details">
    <div class="card-body">
        <h4 class="card-title">Business Details</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                <input
                    type="text"
                    name="company_name"
                    value="{{ old('company_name', $customer->company_name) }}"
                    class="form-control @error('company_name') is-invalid @enderror"
                    id="company_name"
                    autocomplete="organization"
                    {{ $isBusiness ? '' : 'disabled' }}
                >
                @error('company_name')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-3 mb-3">
                <label for="tax_id_type" class="form-label">Tax ID Type</label>
                <select
                    name="tax_id_type"
                    class="form-select @error('tax_id_type') is-invalid @enderror"
                    id="tax_id_type"
                    {{ $isBusiness ? '' : 'disabled' }}
                >
                    <option value="">Select type</option>
                    @foreach ($taxTypes as $taxType)
                        <option value="{{ $taxType }}" {{ old('tax_id_type', $customer->tax_id_type) === $taxType ? 'selected' : '' }}>{{ $taxType }}</option>
                    @endforeach
                </select>
                @error('tax_id_type')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-3 mb-3">
                <label for="tax_id" class="form-label">Tax ID</label>
                <input
                    type="text"
                    name="tax_id"
                    value="{{ old('tax_id', $customer->tax_id) }}"
                    class="form-control @error('tax_id') is-invalid @enderror"
                    id="tax_id"
                    autocomplete="off"
                    {{ $isBusiness ? '' : 'disabled' }}
                >
                @error('tax_id')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h4 class="card-title">Address</h4>
        <div class="row">
            <div class="col-12 mb-3">
                <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                <input
                    type="text"
                    name="address"
                    value="{{ old('address', $customer->address) }}"
                    class="form-control @error('address') is-invalid @enderror"
                    id="address"
                    required
                    autocomplete="street-address"
                >
                @error('address')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                <input
                    type="text"
                    name="city"
                    value="{{ old('city', $customer->city) }}"
                    class="form-control @error('city') is-invalid @enderror"
                    id="city"
                    required
                    autocomplete="address-level2"
                >
                @error('city')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="state" class="form-label">State / Province</label>
                <input
                    type="text"
                    name="state"
                    value="{{ old('state', $customer->state) }}"
                    class="form-control @error('state') is-invalid @enderror"
                    id="state"
                    autocomplete="address-level1"
                >
                @error('state')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="postal_code" class="form-label">Postal Code</label>
                <input
                    type="text"
                    name="postal_code"
                    value="{{ old('postal_code', $customer->postal_code) }}"
                    class="form-control @error('postal_code') is-invalid @enderror"
                    id="postal_code"
                    autocomplete="postal-code"
                >
                @error('postal_code')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="country_code" class="form-label">Country <span class="text-danger">*</span></label>
                <input
                    type="search"
                    id="country_search"
                    class="form-control mb-2"
                    placeholder="Search country..."
                    autocomplete="off"
                    aria-label="Search countries"
                >
                <select
                    name="country_code"
                    class="form-select @error('country_code') is-invalid @enderror"
                    id="country_code"
                    required
                    autocomplete="country"
                >
                    <option value="">Select country</option>
                    @foreach ($countries as $code => $name)
                        <option value="{{ $code }}" {{ old('country_code', $customer->country_code) === $code ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                @error('country_code')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
        <i class="mdi mdi-arrow-left me-1"></i> Back
    </a>
    <button type="submit" class="btn btn-gradient-primary">Save Customer</button>
</div>

@push('scripts')
<script>
(function () {
    var businessSection = document.getElementById('business-details');
    var nameLabel = document.getElementById('name-label-text');
    var countrySelect = document.getElementById('country_code');
    var taxTypeSelect = document.getElementById('tax_id_type');
    var countrySearch = document.getElementById('country_search');
    var euCountries = @json(\App\Support\Countries::vatCodes());

    function suggestedTaxType(code) {
        if (code === 'IN') {
            return 'GSTIN';
        }
        if (code === 'US') {
            return 'EIN';
        }
        if (euCountries.includes(code)) {
            return 'VAT';
        }
        return '';
    }

    function syncType() {
        var checked = document.querySelector('input[name="customer_type"]:checked');
        var isBusiness = checked && checked.value === 'business';

        if (businessSection) {
            businessSection.classList.toggle('d-none', !isBusiness);
            businessSection.querySelectorAll('input, select').forEach(function (el) {
                el.disabled = !isBusiness;
            });
        }

        if (nameLabel) {
            nameLabel.textContent = isBusiness ? 'Contact Name' : 'Full Name';
        }
    }

    document.querySelectorAll('input[name="customer_type"]').forEach(function (radio) {
        radio.addEventListener('change', syncType);
    });

    if (countrySearch && countrySelect) {
        countrySearch.addEventListener('input', function () {
            var term = countrySearch.value.trim().toLowerCase();
            countrySelect.querySelectorAll('option').forEach(function (option) {
                if (option.value === '' || option.selected) {
                    option.hidden = false;
                    return;
                }
                var haystack = (option.textContent + ' ' + option.value).toLowerCase();
                option.hidden = term !== '' && haystack.indexOf(term) === -1;
            });
        });
    }

    if (countrySelect && taxTypeSelect) {
        countrySelect.addEventListener('change', function () {
            if (taxTypeSelect.value === '') {
                var suggestion = suggestedTaxType(countrySelect.value);
                if (suggestion) {
                    taxTypeSelect.value = suggestion;
                }
            }
        });
    }

    syncType();
})();
</script>
@endpush
