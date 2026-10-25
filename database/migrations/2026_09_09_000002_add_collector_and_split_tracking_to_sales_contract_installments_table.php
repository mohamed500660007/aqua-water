<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_contract_installments', function (Blueprint $table) {
            $table->foreignId('collected_by_id')->nullable()->after('paid_amount')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->after('collected_by_id')
                ->constrained('users')->nullOnDelete();

            // القسط المتبقي من دفعة جزئية يُنشأ كسجل جديد مرتبط بأصله
            $table->foreignId('split_from_id')->nullable()->after('sales_rep_id')
                ->constrained('sales_contract_installments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_contract_installments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('split_from_id');
            $table->dropConstrainedForeignId('sales_rep_id');
            $table->dropConstrainedForeignId('collected_by_id');
        });
    }
};
