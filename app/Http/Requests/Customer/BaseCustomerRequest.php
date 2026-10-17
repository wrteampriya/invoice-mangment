<?php

namespace App\Http\Requests\Customer;

use App\Models\Customer;
use App\Support\Countries;
use Illuminate\Foundation\Http\FormRequest;

class BaseCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $input = [];

        foreach (['name', 'company_name', 'email', 'phone', 'tax_id_type', 'tax_id', 'address', 'city', 'state', 'postal_code'] as $field) {
            if ($this->has($field)) {
                $value = trim((string) $this->input($field));
                $input[$field] = $value === '' ? null : $value;
            }
        }

        if (isset($input['email'])) {
            $input['email'] = strtolower($input['email']);
        }

        if ($this->has('country_code')) {
            $code = trim((string) $this->input('country_code'));
            $input['country_code'] = $code === '' ? null : strtoupper($code);
        }

        if (($this->input('customer_type')) === 'individual') {
            $input['company_name'] = null;
            $input['tax_id_type'] = null;
            $input['tax_id'] = null;
        }

        $this->merge($input);
    }

    public function rules(): array
    {
        return [
            'customer_type' => ['required', 'in:'.implode(',', Customer::CUSTOMER_TYPES)],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required_if:customer_type,business', 'nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9\s+\-()]+$/'],
            'tax_id_type' => ['nullable', 'string', 'max:40', 'in:GSTIN,PAN,VAT,EIN,Other'],
            'tax_id' => ['required_with:tax_id_type', 'nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country_code' => ['required', 'string', 'size:2', 'in:'.implode(',', Countries::codes())],
        ];
    }
}
