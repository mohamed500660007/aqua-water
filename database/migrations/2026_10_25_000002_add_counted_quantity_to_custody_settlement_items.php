<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * التسوية تُبنى على الجرد: الكمية الفعلية التي رجع بها الموظف تُقارن برصيد عهدته، والفرق يحدد
 * إن كانت التسوية بالسالب (عجز) أو بالموجب (زيادة). quantity يبقى مقدار الفرق المطلق.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custody_settlement_items', function (Blueprint $table) {
            $table->unsignedInteger('counted_quantity')->nullable()->after('product_id');
        });

        // المُرحّلة: الرصيد بعدها هو الكمية المجرودة. المسودات: الكمية التي تحقق نفس الفرق على العهدة الحالية
        DB::table('custody_settlement_items')
            ->join('custody_settlements', 'custody_settlements.id', '=', 'custody_settlement_items.custody_settlement_id')
            ->leftJoin('employee_stocks', fn ($join) => $join
                ->on('employee_stocks.user_id', '=', 'custody_settlements.user_id')
                ->on('employee_stocks.product_id', '=', 'custody_settlement_items.product_id'))
            ->select([
                'custody_settlement_items.id', 'custody_settlement_items.quantity', 'custody_settlement_items.balance_after',
                'custody_settlements.type', 'custody_settlements.status', 'employee_stocks.quantity as custody',
            ])
            ->orderBy('custody_settlement_items.id')
            ->get()
            ->each(function ($item) {
                $current = (int) $item->custody;
                $counted = $item->status === 'posted'
                    ? (int) $item->balance_after
                    : max(0, $item->type === 'negative' ? $current - $item->quantity : $current + $item->quantity);

                $preview = $item->status === 'posted' ? [] : ['balance_before' => $current, 'balance_after' => $counted];

                DB::table('custody_settlement_items')->where('id', $item->id)->update(['counted_quantity' => $counted] + $preview);
            });
    }

    public function down(): void
    {
        Schema::table('custody_settlement_items', function (Blueprint $table) {
            $table->dropColumn('counted_quantity');
        });
    }
};
