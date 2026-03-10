<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_gateway_configs', function (Blueprint $table) {
            // Add provider_config column untuk simpan config spesifik provider (Mayar/Midtrans)
            $table->json('provider_config')->nullable()->after('config');

            // Update index untuk include provider_config lookups
            $table->index(['store_id', 'provider', 'is_active'], 'pgc_store_provider_active_index');
        });

        // Update existing data: ubah provider 'cash' menjadi null atau default
        // dan set provider yang valid ke mayar/midtrans
        DB::table('payment_gateway_configs')
            ->where('provider', 'cash')
            ->update(['provider' => 'mayar']); // Default ke mayar untuk backward compatibility
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateway_configs', function (Blueprint $table) {
            $table->dropColumn('provider_config');
            $table->dropIndex('pgc_store_provider_active_index');
        });
    }
};
