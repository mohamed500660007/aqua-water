<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_contracts', function (Blueprint $table) {
            $table->string('kind', 20)->default('sale')->after('code');
            $table->date('legal_referred_at')->nullable()->after('remaining');

            $table->index('kind');
            $table->index('legal_referred_at');
            $table->index('installation_date');
        });
    }

    public function down(): void
    {
        Schema::table('sales_contracts', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropIndex(['legal_referred_at']);
            $table->dropIndex(['installation_date']);
            $table->dropColumn(['kind', 'legal_referred_at']);
        });
    }
};
