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
        if (! Schema::hasColumn('transactions', 'payment_gateway_qr_string')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->text('payment_gateway_qr_string')->nullable();
            });
        }

        if (! Schema::hasColumn('transactions', 'status')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('status')->nullable();
            });
        }

        if (! Schema::hasIndex('transactions', ['payment_gateway_reference'])) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('payment_gateway_reference');
            });
        }

        if (! Schema::hasIndex('transactions', ['payment_gateway_transaction_id'])) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('payment_gateway_transaction_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * The `status` column is intentionally never dropped: no migration in this
     * repository creates it, so it may predate this deployment and dropping it on
     * rollback could destroy live data.
     */
    public function down(): void
    {
        if (Schema::hasIndex('transactions', ['payment_gateway_reference'])) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex(['payment_gateway_reference']);
            });
        }

        if (Schema::hasIndex('transactions', ['payment_gateway_transaction_id'])) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex(['payment_gateway_transaction_id']);
            });
        }

        if (Schema::hasColumn('transactions', 'payment_gateway_qr_string')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('payment_gateway_qr_string');
            });
        }
    }
};
