<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('payment_gateway_enabled')->default(false);
            $table->string('payment_gateway_provider')->nullable();
            $table->boolean('payment_gateway_sandbox')->default(true);
            $table->json('payment_config')->nullable();
            $table->json('payment_enabled_methods')->nullable();
        });
    }
};
