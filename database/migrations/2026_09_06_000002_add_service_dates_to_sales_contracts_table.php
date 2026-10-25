<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_contracts', function (Blueprint $table) {
            $table->date('installation_date')->nullable()->after('contract_date');
            $table->date('collection_date')->nullable()->after('installation_date');
            $table->date('maintenance_date')->nullable()->after('collection_date');
        });
    }

    public function down(): void
    {
        Schema::table('sales_contracts', function (Blueprint $table) {
            $table->dropColumn(['installation_date', 'collection_date', 'maintenance_date']);
        });
    }
};
