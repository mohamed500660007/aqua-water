<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            $table->string('stock_source', 20);
            $table->foreignId('warehouse_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);

            $table->date('gift_date');
            $table->string('note', 255)->nullable();

            $table->timestamps();

            $table->index('gift_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gifts');
    }
};
