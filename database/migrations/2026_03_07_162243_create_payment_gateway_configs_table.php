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
        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained()
                ->onDelete('cascade');
            $table->string('provider')->default('cash');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_sandbox')->default(true);
            $table->longText('api_key')->nullable();
            $table->json('config')->nullable();
            $table->json('enabled_methods')->nullable();
            $table->string('webhook_url')->nullable();
            $table->timestamps();

            $table->index('store_id');
            $table->index(['store_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_configs');
    }
};
