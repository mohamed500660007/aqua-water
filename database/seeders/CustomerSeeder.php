<?php

namespace Database\Seeders;

use App\Domain\Customers\Enums\CustomerStatus;
use App\Domain\Customers\Models\Customer;
use App\Support\PhoneNumber;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->customers() as $index => $row) {
            $customer = Customer::updateOrCreate(
                ['code' => 'CUS-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT)],
                [
                    'name' => $row['name'],
                    'governorate' => $row['governorate'],
                    'center' => $row['city'],
                    'status' => $this->status($row['status']),
                    'registered_at' => $row['joined'],
                ],
            );

            $customer->phones()->updateOrCreate(
                ['phone' => PhoneNumber::normalize($row['phone'])],
                ['is_primary' => true],
            );
        }
    }

    /** بيانات تجريبية للعملاء */
    private function customers(): array
    {
        return [
            ['id' => 1,  'name' => 'أحمد سيد عبد الرحمن',  'phone' => '01012345678', 'email' => 'ahmed.sayed@mail.com',   'governorate' => 'القاهرة',    'city' => 'مدينة نصر',     'orders' => 7,  'spent' => 24500, 'status' => 'نشط',     'joined' => '2023-04-12', 'last_order' => '2024-08-14'],
            ['id' => 2,  'name' => 'منى خالد إبراهيم',     'phone' => '01123456789', 'email' => 'mona.khaled@mail.com',   'governorate' => 'الجيزة',     'city' => 'المهندسين',     'orders' => 4,  'spent' => 12800, 'status' => 'نشط',     'joined' => '2023-07-03', 'last_order' => '2024-08-09'],
            ['id' => 3,  'name' => 'محمود عبد العزيز',     'phone' => '01234567890', 'email' => 'mahmoud.aziz@mail.com',  'governorate' => 'الإسكندرية', 'city' => 'سموحة',         'orders' => 11, 'spent' => 41300, 'status' => 'نشط',     'joined' => '2022-11-21', 'last_order' => '2024-08-18'],
            ['id' => 4,  'name' => 'سارة مصطفى حسن',       'phone' => '01098765432', 'email' => 'sara.mostafa@mail.com',  'governorate' => 'القاهرة',    'city' => 'التجمع الخامس', 'orders' => 2,  'spent' => 8650,  'status' => 'محتمل',   'joined' => '2024-05-30', 'last_order' => '2024-06-11'],
            ['id' => 5,  'name' => 'كريم وليد فؤاد',       'phone' => '01155667788', 'email' => 'karim.walid@mail.com',   'governorate' => 'الدقهلية',   'city' => 'المنصورة',      'orders' => 6,  'spent' => 19200, 'status' => 'نشط',     'joined' => '2023-02-17', 'last_order' => '2024-07-28'],
            ['id' => 6,  'name' => 'هبة الله ناصر',        'phone' => '01276543210', 'email' => 'heba.nasser@mail.com',   'governorate' => 'الغربية',    'city' => 'طنطا',          'orders' => 3,  'spent' => 9400,  'status' => 'غير نشط', 'joined' => '2023-09-08', 'last_order' => '2024-01-22'],
            ['id' => 7,  'name' => 'عمرو شعبان علي',       'phone' => '01033221144', 'email' => 'amr.shaaban@mail.com',   'governorate' => 'أسيوط',      'city' => 'أسيوط الجديدة', 'orders' => 5,  'spent' => 16750, 'status' => 'نشط',     'joined' => '2023-06-14', 'last_order' => '2024-08-02'],
            ['id' => 8,  'name' => 'نورهان سامي فتحي',     'phone' => '01199887766', 'email' => 'nourhan.sami@mail.com',  'governorate' => 'بورسعيد',    'city' => 'بورسعيد',       'orders' => 1,  'spent' => 3200,  'status' => 'محتمل',   'joined' => '2024-07-19', 'last_order' => '2024-07-19'],
            ['id' => 9,  'name' => 'ياسر فتحي الشناوي',    'phone' => '01244556677', 'email' => 'yasser.fathy@mail.com',  'governorate' => 'الشرقية',    'city' => 'الزقازيق',      'orders' => 9,  'spent' => 33900, 'status' => 'نشط',     'joined' => '2022-08-05', 'last_order' => '2024-08-16'],
            ['id' => 10, 'name' => 'دينا عادل رمضان',      'phone' => '01066778899', 'email' => 'dina.adel@mail.com',     'governorate' => 'الجيزة',     'city' => '6 أكتوبر',      'orders' => 8,  'spent' => 27600, 'status' => 'نشط',     'joined' => '2023-01-29', 'last_order' => '2024-08-11'],
            ['id' => 11, 'name' => 'مصطفى كمال الدين',     'phone' => '01111223344', 'email' => 'mostafa.kamal@mail.com', 'governorate' => 'القليوبية',  'city' => 'شبرا الخيمة',   'orders' => 2,  'spent' => 5900,  'status' => 'غير نشط', 'joined' => '2023-12-02', 'last_order' => '2024-02-15'],
            ['id' => 12, 'name' => 'إيمان طارق محمد',      'phone' => '01288776655', 'email' => 'eman.tarek@mail.com',    'governorate' => 'القاهرة',    'city' => 'المعادي',       'orders' => 6,  'spent' => 21450, 'status' => 'نشط',     'joined' => '2023-03-25', 'last_order' => '2024-08-05'],
            ['id' => 13, 'name' => 'شركة النيل للمقاولات', 'phone' => '0223456789',  'email' => 'info@nilecon.com',       'governorate' => 'القاهرة',    'city' => 'وسط البلد',     'orders' => 14, 'spent' => 96400, 'status' => 'نشط',     'joined' => '2022-05-10', 'last_order' => '2024-08-19'],
            ['id' => 14, 'name' => 'رامي جورج نصيف',       'phone' => '01277889900', 'email' => 'ramy.george@mail.com',   'governorate' => 'الإسكندرية', 'city' => 'العجمي',        'orders' => 3,  'spent' => 10300, 'status' => 'محتمل',   'joined' => '2024-04-08', 'last_order' => '2024-05-27'],
        ];
    }

    private function status(string $label): CustomerStatus
    {
        return match ($label) {
            'نشط' => CustomerStatus::Active,
            default => CustomerStatus::Inactive,
        };
    }
}
