<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL requires using ALTER TABLE to modify enum values
        DB::statement("ALTER TABLE transactions MODIFY payment_method ENUM('cash', 'transfer', 'qris', 'digital', 'multi', 'split') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY payment_method ENUM('cash', 'transfer', 'qris') NOT NULL");
    }
};
