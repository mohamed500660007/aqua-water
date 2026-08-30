<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('maintenance_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->date('contract_date');
            $table->string('type', 20);
            $table->text('notes')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid', 12, 2)->default(0);
            $table->decimal('remaining', 12, 2)->default(0);

            $table->unsignedSmallInteger('installments_count')->nullable();
            $table->unsignedSmallInteger('installment_period_months')->nullable();
            $table->date('first_due_date')->nullable();
            $table->decimal('installment_amount', 12, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('contract_date');
            $table->index('type');
            $table->index('customer_id');
        });

        Schema::create('sales_contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name', 150);
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('total', 12, 2);
            $table->timestamps();

            $table->index('sales_contract_id');
            $table->index('product_id');
        });

        Schema::create('sales_contract_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique(['sales_contract_id', 'number']);
            $table->index('due_date');
        });

        // نسخة ثابتة من بنود خطة الصيانة وقت التعاقد حتى لا تتأثر العقود بتعديل الخطة لاحقًا
        Schema::create('sales_contract_maintenance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 150);
            $table->unsignedSmallInteger('interval_months');
            $table->date('replacement_date');
            $table->timestamps();

            $table->index('sales_contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_contract_maintenance_items');
        Schema::dropIfExists('sales_contract_installments');
        Schema::dropIfExists('sales_contract_items');
        Schema::dropIfExists('sales_contracts');
    }
};
