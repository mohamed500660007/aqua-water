<?php

namespace Tests\Unit;

use App\Support\NationalId;
use PHPUnit\Framework\TestCase;

class NationalIdTest extends TestCase
{
    public function test_it_reads_birth_date_gender_and_governorate(): void
    {
        $male = NationalId::parse('29005121201234');

        $this->assertSame('1990-05-12', $male['birth_date']->toDateString());
        $this->assertSame(NationalId::MALE, $male['gender']);
        $this->assertSame('الدقهلية', $male['governorate']);

        $female = NationalId::parse('30102280101224');
        $this->assertSame('2001-02-28', $female['birth_date']->toDateString());
        $this->assertSame(NationalId::FEMALE, $female['gender']);
        $this->assertSame('القاهرة', $female['governorate']);
    }

    public function test_it_accepts_arabic_digits(): void
    {
        $this->assertSame('1990-05-12', NationalId::parse('٢٩٠٠٥١٢١٢٠١٢٣٤')['birth_date']->toDateString());
    }

    public function test_it_rejects_malformed_numbers(): void
    {
        foreach ([
            '2900512120123',   // 13 رقمًا
            '19005121201234',  // قرن غير صالح
            '29002301201234',  // 30 فبراير
            '29005129901234',  // كود محافظة غير موجود
            '39912311201234',  // تاريخ ميلاد في المستقبل
            null,
        ] as $invalid) {
            $this->assertNull(NationalId::parse($invalid), "قُبل رقم غير صالح: {$invalid}");
        }
    }
}
