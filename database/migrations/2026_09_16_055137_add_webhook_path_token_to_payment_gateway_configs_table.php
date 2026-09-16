<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_gateway_configs', function (Blueprint $table) {
            $table->string('webhook_path_token', 64)->nullable()->unique()->after('webhook_url');
        });

        DB::table('payment_gateway_configs')
            ->whereNull('webhook_path_token')
            ->orderBy('id')
            ->eachById(function (object $config): void {
                DB::table('payment_gateway_configs')
                    ->where('id', $config->id)
                    ->update(['webhook_path_token' => Str::random(48)]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateway_configs', function (Blueprint $table) {
            $table->dropUnique(['webhook_path_token']);
            $table->dropColumn('webhook_path_token');
        });
    }
};
