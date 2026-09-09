<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_contract_installments', function (Blueprint $table) {
            $table->date('paid_at')->nullable()->after('amount');
            $table->decimal('paid_amount', 12, 2)->nullable()->after('paid_at');
            $table->string('note', 255)->nullable()->after('paid_amount');

            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales_contract_installments', function (Blueprint $table) {
            $table->dropIndex(['paid_at']);
            $table->dropColumn(['paid_at', 'paid_amount', 'note']);
        });
    }
};
