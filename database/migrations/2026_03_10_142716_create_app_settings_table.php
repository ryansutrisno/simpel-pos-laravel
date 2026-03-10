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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->nullable();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('date_format')->default('d/m/Y');
            $table->string('time_format')->default('H:i');
            $table->string('currency')->default('IDR');
            $table->string('currency_format')->default('id_ID');
            $table->string('email_from')->nullable();
            $table->string('email_from_name')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->timestamps();

            // Unique constraint untuk singleton pattern - hanya 1 row di tabel ini
            $table->unique('id');

            // Index untuk maintenance mode (sering di-check)
            $table->index('maintenance_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
