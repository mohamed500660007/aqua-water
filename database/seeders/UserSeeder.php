<?php

namespace Database\Seeders;

use App\Domain\Users\Enums\UserStatus;
use App\Domain\Users\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->staff() as $row) {
            User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'phone' => PhoneNumber::normalize($row['phone']),
                    'role' => $row['role'],
                    'department' => $row['department'],
                    'branch' => $row['branch'],
                    'status' => $this->status($row['status']),
                ],
            );
        }
    }

    /** بيانات تجريبية للموظفين — مكانها الطبيعي داخل البذور وليس داخل التطبيق */
    private function staff(): array
    {
        return [
            ['id' => 1, 'name' => 'خالد منصور',       'role' => 'مدير النظام',  'department' => 'الإدارة',      'phone' => '01001112233', 'email' => 'khaled@aquawater.eg', 'status' => 'نشط',   'last_login' => '2024-08-23 09:12', 'branch' => 'القاهرة'],
            ['id' => 2, 'name' => 'ريهام عبد الفتاح', 'role' => 'مدير مبيعات',  'department' => 'المبيعات',     'phone' => '01002223344', 'email' => 'reham@aquawater.eg',  'status' => 'نشط',   'last_login' => '2024-08-23 08:40', 'branch' => 'القاهرة'],
            ['id' => 3, 'name' => 'سيد عبد الحميد',   'role' => 'فني تركيب',    'department' => 'الفنيين',      'phone' => '01003334455', 'email' => 'sayed@aquawater.eg',  'status' => 'نشط',   'last_login' => '2024-08-22 17:55', 'branch' => 'الإسكندرية'],
            ['id' => 4, 'name' => 'مروة السيد',       'role' => 'خدمة عملاء',   'department' => 'السكرتارية',   'phone' => '01004445566', 'email' => 'marwa@aquawater.eg',  'status' => 'نشط',   'last_login' => '2024-08-23 10:02', 'branch' => 'القاهرة'],
            ['id' => 5, 'name' => 'طارق زكريا',       'role' => 'محاسب',        'department' => 'الحسابات',     'phone' => '01005556677', 'email' => 'tarek@aquawater.eg',  'status' => 'نشط',   'last_login' => '2024-08-22 15:30', 'branch' => 'القاهرة'],
            ['id' => 6, 'name' => 'هشام لطفي',        'role' => 'مندوب مبيعات', 'department' => 'المبيعات',     'phone' => '01006667788', 'email' => 'hesham@aquawater.eg', 'status' => 'إجازة', 'last_login' => '2024-08-18 12:11', 'branch' => 'الدقهلية'],
            ['id' => 7, 'name' => 'أمينة رشدي',       'role' => 'خدمة عملاء',   'department' => 'السكرتارية',   'phone' => '01007778899', 'email' => 'amina@aquawater.eg',  'status' => 'نشط',   'last_login' => '2024-08-23 09:48', 'branch' => 'الجيزة'],
            ['id' => 8, 'name' => 'وائل الشربيني',    'role' => 'فني تركيب',    'department' => 'الفنيين',      'phone' => '01008889900', 'email' => 'wael@aquawater.eg',   'status' => 'موقوف', 'last_login' => '2024-07-30 13:20', 'branch' => 'الشرقية'],
        ];
    }

    private function status(string $label): UserStatus
    {
        return match ($label) {
            'إجازة' => UserStatus::OnLeave,
            'موقوف' => UserStatus::Suspended,
            default => UserStatus::Active,
        };
    }
}
