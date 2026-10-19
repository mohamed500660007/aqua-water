<?php

namespace App\Domain\Settings\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * إعداد عام بقيمة واحدة للنظام كله — تعديله يسري على الجميع فورًا.
 */
class Setting extends Model
{
    /** الملاحظة المطبوعة على كل إيصالات الأقساط */
    public const RECEIPT_NOTE = 'receipt_note';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->whereKey($key)->value('value') ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
