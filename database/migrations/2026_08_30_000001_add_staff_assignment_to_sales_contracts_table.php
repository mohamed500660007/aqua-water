<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_contracts', function (Blueprint $table) {
            $table->foreignId('sales_rep_id')->nullable()->after('customer_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('installer_id')->nullable()->after('sales_rep_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_rep_id');
            $table->dropConstrainedForeignId('installer_id');
        });
    }
};
