<?php

namespace App\Http\Requests\Products;

use App\Domain\Products\Data\ProductData;
use App\Domain\Products\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'sku' => ['required', 'string', 'max:32', Rule::unique('products', 'sku')->ignore($product)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'warranty' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
        ];
    }

    public function toData(): ProductData
    {
        return ProductData::fromArray($this->validated());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => strtoupper(trim((string) $this->input('sku'))) ?: null,
            'name' => trim((string) $this->input('name')) ?: null,
            'category' => trim((string) $this->input('category')) ?: null,
            'warranty' => trim((string) $this->input('warranty')) ?: null,
        ]);
    }

    public function attributes(): array
    {
        return [
            'sku' => 'كود المنتج',
            'name' => 'اسم المنتج',
            'category' => 'التصنيف',
            'price' => 'سعر البيع',
            'cost' => 'سعر التكلفة',
            'warranty' => 'الضمان',
            'status' => 'الحالة',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'كود المنتج مستخدم بالفعل.',
            'price.min' => 'سعر البيع لا يمكن أن يكون سالبًا.',
            'cost.min' => 'سعر التكلفة لا يمكن أن يكون سالبًا.',
        ];
    }
}
