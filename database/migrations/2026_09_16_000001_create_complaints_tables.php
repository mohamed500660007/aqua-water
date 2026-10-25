<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('complaint_date');
            $table->decimal('visit_fee', 12, 2)->default(0);
            $table->boolean('resolved')->default(false);
            $table->string('note', 500)->nullable();

            $table->timestamps();

            $table->index('complaint_date');
            $table->index('resolved');
        });

        Schema::create('complaint_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            $table->string('stock_source', 20);
            $table->foreignId('warehouse_id')->nullable()->constrained()->restrictOnDelete();

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_parts');
        Schema::dropIfExists('complaints');
    }
};
