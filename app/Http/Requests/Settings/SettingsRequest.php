<?php

namespace App\Http\Requests\Settings;

use App\Domain\Settings\Settings;
use Illuminate\Foundation\Http\FormRequest;

/**
 * حفظ قسم واحد من الإعدادات. القواعد تُبنى من تعريف الحقول نفسه في Settings فلا تتكرر،
 * والقوائم تصل نصًا بسطر لكل عنصر فتُحوَّل لمصفوفة قبل التحقق.
 */
class SettingsRequest extends FormRequest
{
    private const LIST_LIMIT = 100;

    public function rules(): array
    {
        $rules = [];

        foreach ($this->fields() as $key => $field) {
            $presence = ($field['required'] ?? false) || in_array($field['type'], [Settings::INTEGER, Settings::DECIMAL], true)
                ? 'required'
                : 'nullable';

            $rules[$key] = match ($field['type']) {
                Settings::INTEGER => [$presence, 'integer', 'min:'.$field['min'], 'max:'.$field['max']],
                Settings::DECIMAL => [$presence, 'numeric', 'min:'.$field['min'], 'max:'.$field['max']],
                Settings::LIST => [$presence, 'array', 'max:'.($field['max_items'] ?? self::LIST_LIMIT)],
                default => array_values(array_filter([$presence, 'string', 'max:'.$field['max'], ($field['email'] ?? false) ? 'email' : null])),
            };

            if ($field['type'] === Settings::LIST) {
                $rules[$key.'.*'] = ['string', 'max:100'];
            }
        }

        return $rules;
    }

    /** @return array<string, mixed> */
    public function values(): array
    {
        return $this->safe()->only(array_keys($this->fields()));
    }

    public function section(): string
    {
        return $this->route('section');
    }

    protected function prepareForValidation(): void
    {
        $lists = collect($this->fields())
            ->filter(fn (array $field) => $field['type'] === Settings::LIST)
            ->mapWithKeys(fn (array $field, string $key) => [$key => Settings::lines($this->input($key, ''))]);

        $this->merge($lists->all());
    }

    public function attributes(): array
    {
        return collect($this->fields())->mapWithKeys(fn (array $field, string $key) => [$key => $field['label']])->all();
    }

    public function messages(): array
    {
        return [
            '*.required' => 'خانة «:attribute» مطلوبة.',
            '*.max' => '«:attribute» أطول من المسموح.',
            '*.array' => '«:attribute» لازم تكون سطر لكل عنصر.',
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function fields(): array
    {
        return Settings::sections()[$this->route('section')]['fields'] ?? [];
    }
}
