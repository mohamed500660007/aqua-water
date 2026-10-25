<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * سعر شراء القطعة لكل بند طلبية. الطلبيات القديمة سُجّلت بإجمالي مبلغ فقط، فيبقى سعرها فارغًا —
 * إلا الطلبية ذات المنتج الواحد: سعر قطعتها معروف بدقة (الإجمالي ÷ الكمية).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable()->after('quantity');
        });

        // تُقرأ المعرّفات أولًا: MySQL يرفض تحديث جدول بشرط استعلام فرعي على الجدول نفسه
        $singleProductOrders = DB::table('purchase_order_items')
            ->groupBy('purchase_order_id')
            ->havingRaw('COUNT(*) = 1')
            ->pluck('purchase_order_id');

        DB::table('purchase_order_items')
            ->whereIn('purchase_order_id', $singleProductOrders)
            ->where('quantity', '>', 0)
            ->update([
                'unit_price' => DB::raw('ROUND((SELECT total_amount FROM purchase_orders WHERE purchase_orders.id = purchase_order_items.purchase_order_id) / quantity, 2)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
};
