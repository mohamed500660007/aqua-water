<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فائدة التقسيط: مبلغ يُضاف على المتبقي بعد المقدم، فيدخل في إجمالي العقد وفي الأقساط.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_contracts', function (Blueprint $table) {
            $table->decimal('interest', 12, 2)->default(0)->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('sales_contracts', function (Blueprint $table) {
            $table->dropColumn('interest');
        });
    }
};
