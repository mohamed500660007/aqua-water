<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('kind', 20)->default('other')->after('category');
            $table->index('kind');
        });

        // تصنيف مبدئي للمنتجات القائمة من نص التصنيف الحر — يمكن تصحيحه لاحقًا من نموذج المنتج.
        // الشمعات أولًا لأن تصنيفها «شمعات وفلاتر بديلة» يحتوي أيضًا كلمة «فلاتر».
        DB::table('products')->where('category', 'like', '%شمع%')->update(['kind' => 'cartridge']);

        DB::table('products')
            ->where('kind', 'other')
            ->where(fn ($query) => $query
                ->where('category', 'like', '%فلاتر%')
                ->orWhere('category', 'like', '%جهاز%')
                ->orWhere('category', 'like', '%أجهزة%'))
            ->update(['kind' => 'device']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn('kind');
        });
    }
};
