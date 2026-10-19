<?php

namespace App\Http\Requests\Maintenance;

use App\Domain\Maintenance\Models\CandleStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CandleStageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'stages' => ['array'],
            'stages.*.product_id' => ['nullable', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'stages.*.interval_months' => ['required_with:stages.*.product_id', 'nullable', 'integer', 'min:1', 'max:120'],
        ];
    }

    /** @return array<int, array{product_id: int, interval_months: int}> المراحل المضبوطة فقط، مفهرسة برقمها */
    public function stages(): array
    {
        return collect($this->validated('stages', []))
            ->filter(fn (array $stage, $position) => filled($stage['product_id'] ?? null) && in_array((int) $position, CandleStage::POSITIONS, true))
            ->mapWithKeys(fn (array $stage, $position) => [(int) $position => [
                'product_id' => (int) $stage['product_id'],
                'interval_months' => (int) $stage['interval_months'],
            ]])
            ->all();
    }

    public function attributes(): array
    {
        return [
            'stages.*.product_id' => 'شمعة المرحلة',
            'stages.*.interval_months' => 'دورية التغيير',
        ];
    }

    public function messages(): array
    {
        return ['stages.*.interval_months.required_with' => 'حدّد كل كام شهر تتغير شمعة هذه المرحلة.'];
    }
}
