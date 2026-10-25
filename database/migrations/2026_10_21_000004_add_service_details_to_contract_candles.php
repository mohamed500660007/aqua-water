<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ملف الصيانة الجديد: كل شمعة على العقد تُغيَّر مرة واحدة، فتُحفظ تفاصيل تغييرها على سطرها نفسه:
 * الفني، تاريخ التغيير (serviced_at — كان last_replaced_at)، قطاعي/جملة، السعر والخصم والضمان، وعمولة الفني.
 * product_id = الشمعة التي خُصمت فعلًا من عهدة الفني.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_contract_maintenance_items', function (Blueprint $table) {
            $table->renameColumn('last_replaced_at', 'serviced_at');
        });

        Schema::table('sales_contract_maintenance_items', function (Blueprint $table) {
            $table->foreignId('technician_id')->nullable()->after('product_name')->constrained('users')->nullOnDelete();
            $table->string('service_type', 20)->nullable()->after('serviced_at');
            $table->decimal('price', 12, 2)->nullable()->after('service_type');
            $table->decimal('discount', 12, 2)->default(0)->after('price');
            $table->boolean('under_warranty')->default(false)->after('discount');
            $table->decimal('technician_commission', 12, 2)->default(0)->after('under_warranty');

            $table->index('serviced_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales_contract_maintenance_items', function (Blueprint $table) {
            $table->dropIndex(['serviced_at']);
            $table->dropConstrainedForeignId('technician_id');
            $table->dropColumn(['service_type', 'price', 'discount', 'under_warranty', 'technician_commission']);
        });

        Schema::table('sales_contract_maintenance_items', function (Blueprint $table) {
            $table->renameColumn('serviced_at', 'last_replaced_at');
        });
    }
};
