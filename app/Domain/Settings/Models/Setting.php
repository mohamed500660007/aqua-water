<?php

namespace App\Domain\Settings\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * قيمة إعداد واحدة للنظام كله. القيمة تُخزَّن JSON حتى تحفظ القوائم والأرقام بأنواعها؛
 * تعريف كل مفتاح (نوعه، قيمته الافتراضية، قواعده) في App\Domain\Settings\Settings.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }
}
