<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('payment_gateway_enabled')->default(false)->after('show_qr_code');
            $table->string('payment_gateway_provider')->nullable()->after('payment_gateway_enabled');
            $table->boolean('payment_gateway_sandbox')->default(true)->after('payment_gateway_provider');
            $table->json('payment_config')->nullable()->after('payment_gateway_sandbox');
            $table->json('payment_enabled_methods')->nullable()->after('payment_config');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway_enabled',
                'payment_gateway_provider',
                'payment_gateway_sandbox',
                'payment_config',
                'payment_enabled_methods',
            ]);
        });
    }
};
