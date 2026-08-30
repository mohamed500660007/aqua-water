<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 150);
            $table->string('national_id', 14)->nullable()->unique();
            $table->string('governorate', 100)->nullable();
            $table->string('center', 100)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->date('registered_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('status');
            $table->index('registered_at');
            $table->index(['governorate', 'center', 'village']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
