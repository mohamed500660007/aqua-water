<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بيانات بطاقة الرقم القومي والبيانات الشخصية للموظف — كلها اختيارية حتى لا تتأثر الحسابات القائمة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // البطاقة
            $table->string('national_id', 14)->nullable()->unique()->after('phone');
            $table->date('national_id_issued_at')->nullable()->after('national_id');
            $table->date('national_id_expires_at')->nullable()->after('national_id_issued_at');
            $table->string('national_id_issuer', 100)->nullable()->after('national_id_expires_at');
            $table->string('card_address', 500)->nullable()->after('national_id_issuer');
            $table->string('card_occupation', 100)->nullable()->after('card_address');
            $table->string('religion', 20)->nullable()->after('card_occupation');
            $table->string('marital_status', 20)->nullable()->after('religion');

            // البيانات الشخصية
            $table->date('birth_date')->nullable()->after('marital_status');
            $table->string('gender', 10)->nullable()->after('birth_date');
            $table->string('birth_place', 100)->nullable()->after('gender');
            $table->string('nationality', 50)->nullable()->after('birth_place');
            $table->string('qualification', 150)->nullable()->after('nationality');
            $table->string('military_status', 20)->nullable()->after('qualification');
            $table->string('secondary_phone', 20)->nullable()->after('military_status');

            // السكن الحالي
            $table->string('governorate', 100)->nullable()->after('secondary_phone');
            $table->string('city', 100)->nullable()->after('governorate');
            $table->string('address', 500)->nullable()->after('city');

            // جهة اتصال للطوارئ
            $table->string('emergency_contact_name', 150)->nullable()->after('address');
            $table->string('emergency_contact_relation', 50)->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_relation');

            $table->date('hire_date')->nullable()->after('branch');
            $table->text('notes')->nullable()->after('maintenance_commission');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['national_id']);
            $table->dropColumn([
                'national_id', 'national_id_issued_at', 'national_id_expires_at', 'national_id_issuer',
                'card_address', 'card_occupation', 'religion', 'marital_status',
                'birth_date', 'gender', 'birth_place', 'nationality', 'qualification', 'military_status', 'secondary_phone',
                'governorate', 'city', 'address',
                'emergency_contact_name', 'emergency_contact_relation', 'emergency_contact_phone',
                'hire_date', 'notes',
            ]);
        });
    }
};
