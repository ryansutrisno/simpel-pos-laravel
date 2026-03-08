<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('end_of_days', function (Blueprint $table) {
            $table->decimal('total_digital_sales', 15, 2)->default(0)->after('total_qris_sales');
            $table->decimal('total_invoice_sales', 15, 2)->default(0)->after('total_digital_sales');
            $table->decimal('total_multi_sales', 15, 2)->default(0)->after('total_invoice_sales');
            $table->decimal('total_split_sales', 15, 2)->default(0)->after('total_multi_sales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('end_of_days', function (Blueprint $table) {
            $table->dropColumn([
                'total_digital_sales',
                'total_invoice_sales',
                'total_multi_sales',
                'total_split_sales',
            ]);
        });
    }
};
