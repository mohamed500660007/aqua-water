<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 20);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['customer_id', 'phone']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_phones');
    }
};
