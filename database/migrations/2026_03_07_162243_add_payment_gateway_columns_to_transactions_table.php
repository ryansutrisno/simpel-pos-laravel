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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_gateway_provider')->nullable()->after('payment_method');
            $table->string('payment_gateway_reference')->nullable()->after('payment_gateway_provider');
            $table->string('payment_gateway_transaction_id')->nullable()->after('payment_gateway_reference');
            $table->string('payment_gateway_status')->nullable()->after('payment_gateway_transaction_id');
            $table->timestamp('payment_gateway_expires_at')->nullable()->after('payment_gateway_status');
            $table->timestamp('paid_at')->nullable()->after('payment_gateway_expires_at');

            $table->index('payment_gateway_provider');
            $table->index('payment_gateway_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway_provider',
                'payment_gateway_reference',
                'payment_gateway_transaction_id',
                'payment_gateway_status',
                'payment_gateway_expires_at',
                'paid_at',
            ]);
        });
    }
};
