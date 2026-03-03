<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('reorder_point')->default(10)->after('stock');
            $table->integer('reorder_quantity')->default(50)->after('reorder_point');
            $table->timestamp('last_reorder_alert_at')->nullable()->after('reorder_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['reorder_point', 'reorder_quantity', 'last_reorder_alert_at']);
        });
    }
};
