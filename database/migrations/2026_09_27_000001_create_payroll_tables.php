<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('base_salary', 12, 2)->default(0)->after('status');
            $table->decimal('installment_commission', 12, 2)->default(10)->after('base_salary');
            $table->decimal('device_commission', 12, 2)->default(0)->after('installment_commission');
            $table->decimal('maintenance_commission', 12, 2)->default(0)->after('device_commission');
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('month');
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->unsignedInteger('installment_count')->default(0);
            $table->decimal('installment_total', 12, 2)->default(0);
            $table->decimal('installment_commission', 12, 2)->default(10);
            $table->unsignedInteger('device_count')->default(0);
            $table->decimal('device_commission', 12, 2)->default(0);
            $table->unsignedInteger('maintenance_count')->default(0);
            $table->decimal('maintenance_commission', 12, 2)->default(0);
            $table->decimal('adjustment', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'month']);
            $table->index('month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'base_salary', 'installment_commission', 'device_commission', 'maintenance_commission',
            ]);
        });
    }
};
