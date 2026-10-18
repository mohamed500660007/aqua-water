<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * قراءة الرقم القومي المصري (14 رقمًا): القرن + تاريخ الميلاد (YYMMDD) + كود محافظة الميلاد
 * + رقم مسلسل خانته الأخيرة تحدد النوع (فردي ذكر، زوجي أنثى) + رقم تحقق.
 */
final class NationalId
{
    public const MALE = 'male';

    public const FEMALE = 'female';

    private const GOVERNORATES = [
        '01' => 'القاهرة', '02' => 'الإسكندرية', '03' => 'بورسعيد', '04' => 'السويس',
        '11' => 'دمياط', '12' => 'الدقهلية', '13' => 'الشرقية', '14' => 'القليوبية', '15' => 'كفر الشيخ',
        '16' => 'الغربية', '17' => 'المنوفية', '18' => 'البحيرة', '19' => 'الإسماعيلية',
        '21' => 'الجيزة', '22' => 'بني سويف', '23' => 'الفيوم', '24' => 'المنيا', '25' => 'أسيوط',
        '26' => 'سوهاج', '27' => 'قنا', '28' => 'أسوان', '29' => 'الأقصر',
        '31' => 'البحر الأحمر', '32' => 'الوادي الجديد', '33' => 'مطروح', '34' => 'شمال سيناء', '35' => 'جنوب سيناء',
        '88' => 'خارج الجمهورية',
    ];

    /** @return list<string> أسماء المحافظات المصرية (بدون «خارج الجمهورية») */
    public static function governorates(): array
    {
        return array_values(array_diff(self::GOVERNORATES, ['خارج الجمهورية']));
    }

    /**
     * null لو الرقم لا يطابق البنية (قرن أو تاريخ أو محافظة غير صالحة، أو تاريخ ميلاد في المستقبل).
     *
     * @return array{birth_date: CarbonImmutable, gender: string, governorate: string}|null
     */
    public static function parse(?string $nationalId): ?array
    {
        $digits = Digits::normalize($nationalId);

        if (! preg_match('/^([23])(\d{2})(\d{2})(\d{2})(\d{2})\d{3}(\d)\d$/', $digits, $parts)) {
            return null;
        }

        [, $century, $year, $month, $day, $governorate, $genderDigit] = $parts;
        $fullYear = ($century === '2' ? 1900 : 2000) + (int) $year;

        if (! checkdate((int) $month, (int) $day, $fullYear) || ! isset(self::GOVERNORATES[$governorate])) {
            return null;
        }

        $birthDate = CarbonImmutable::create($fullYear, (int) $month, (int) $day);

        if ($birthDate->isFuture()) {
            return null;
        }

        return [
            'birth_date' => $birthDate,
            'gender' => (int) $genderDigit % 2 === 1 ? self::MALE : self::FEMALE,
            'governorate' => self::GOVERNORATES[$governorate],
        ];
    }
}
