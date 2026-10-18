<?php

namespace App\Http\Requests\Customers;

use App\Domain\Customers\Data\CustomerData;
use App\Domain\Customers\Data\CustomerInput;
use App\Domain\Customers\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return CustomerInput::rules($this->customer());
    }

    public function toData(): CustomerData
    {
        return CustomerData::fromArray($this->validated());
    }

    protected function prepareForValidation(): void
    {
        $this->merge(CustomerInput::normalize($this->all()));
    }

    public function attributes(): array
    {
        return CustomerInput::attributes();
    }

    public function messages(): array
    {
        return CustomerInput::messages();
    }

    private function customer(): ?Customer
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer ? $customer : null;
    }
}
