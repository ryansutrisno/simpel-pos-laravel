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
        if (! Schema::hasColumn('users', 'is_demo_account')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_demo_account')->default(false);
            });
        }

        if (! Schema::hasColumn('users', 'demo_access_expires_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('demo_access_expires_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'demo_access_expires_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('demo_access_expires_at');
            });
        }

        if (Schema::hasColumn('users', 'is_demo_account')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_demo_account');
            });
        }
    }
};
