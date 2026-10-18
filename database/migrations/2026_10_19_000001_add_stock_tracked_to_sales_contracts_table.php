<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // العقود القديمة المستوردة من الإكسيل سُلِّمت أجهزتها قبل النظام، فلا تُخصم ولا تُرد من المخزون
        Schema::table('sales_contracts', function (Blueprint $table) {
            $table->boolean('stock_tracked')->default(true)->after('stock_source');
        });
    }

    public function down(): void
    {
        Schema::table('sales_contracts', function (Blueprint $table) {
            $table->dropColumn('stock_tracked');
        });
    }
};
